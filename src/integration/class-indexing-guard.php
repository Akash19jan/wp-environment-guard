<?php
/**
 * Integrates the indexing policy with WordPress.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Integration;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use Akash_Aagrawal\Environment_Guard\Policy\Decision;
use Akash_Aagrawal\Environment_Guard\Policy\Indexing_Policy;
use Akash_Aagrawal\Environment_Guard\Support\Policy_Event_Emitter;

/**
 * Prevents non-production sites from advertising indexable content.
 */
final class Indexing_Guard implements Hookable {

	/**
	 * Whether this adapter has emitted its decision during the current request.
	 *
	 * @var bool
	 */
	private bool $decision_emitted = false;

	/**
	 * Creates the WordPress indexing adapter.
	 *
	 * @param Indexing_Policy      $policy        Indexing policy.
	 * @param Policy_Event_Emitter $event_emitter Privacy-safe event emitter.
	 */
	public function __construct(
		private readonly Indexing_Policy $policy,
		private readonly Policy_Event_Emitter $event_emitter
	) {}

	/**
	 * Registers WordPress indexing filters.
	 */
	public function register_hooks(): void {
		add_filter( 'pre_option_blog_public', array( $this, 'filter_blog_public' ) );
		add_filter( 'wp_robots', array( $this, 'filter_robots' ) );
		add_filter( 'wp_headers', array( $this, 'filter_headers' ) );
		// Runtime filtering does not change the stored privacy option or its cache.
		// phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.robots_txt
		add_filter( 'robots_txt', array( $this, 'filter_robots_txt' ) );
		add_filter( 'wp_sitemaps_enabled', array( $this, 'filter_sitemaps_enabled' ) );
	}

	/**
	 * Overrides the public-site option while indexing protection is active.
	 *
	 * @param mixed $pre_option Previously filtered short-circuit value.
	 * @return mixed Zero when protected; the previous value otherwise.
	 */
	public function filter_blog_public( mixed $pre_option ): mixed {
		$decision = $this->policy->decide();

		if ( ! $decision->is_blocked() ) {
			return $pre_option;
		}

		$this->emit_once( $decision );

		return 0;
	}

	/**
	 * Adds restrictive robots directives without removing existing directives.
	 *
	 * @param array<string, bool|string> $robots Existing robots directives.
	 * @return array<string, bool|string> Filtered robots directives.
	 */
	public function filter_robots( array $robots ): array {
		$decision = $this->policy->decide();

		if ( ! $decision->is_blocked() ) {
			return $robots;
		}

		$this->emit_once( $decision );

		$robots['noarchive'] = true;
		$robots['nofollow']  = true;
		$robots['noindex']   = true;

		return $robots;
	}

	/**
	 * Adds a defense-in-depth X-Robots-Tag response header.
	 *
	 * @param array<string, string> $headers Existing response headers.
	 * @return array<string, string> Filtered response headers.
	 */
	public function filter_headers( array $headers ): array {
		$decision = $this->policy->decide();

		if ( ! $decision->is_blocked() ) {
			return $headers;
		}

		$this->emit_once( $decision );

		$directive = 'noindex, nofollow, noarchive';

		if ( isset( $headers['X-Robots-Tag'] ) && '' !== trim( $headers['X-Robots-Tag'] ) ) {
			$directive = rtrim( trim( $headers['X-Robots-Tag'] ), ', ' ) . ', ' . $directive;
		}

		$headers['X-Robots-Tag'] = $directive;

		return $headers;
	}

	/**
	 * Adds a catch-all disallow rule to the virtual robots.txt response.
	 *
	 * @param string $output Existing robots.txt content.
	 * @return string Filtered robots.txt content.
	 */
	public function filter_robots_txt( string $output ): string {
		$decision = $this->policy->decide();

		if ( ! $decision->is_blocked() ) {
			return $output;
		}

		$this->emit_once( $decision );

		if ( 1 === preg_match( '/^[\t ]*Disallow:[\t ]*\/[\t ]*$/mi', $output ) ) {
			return $output;
		}

		$protected_rules = "User-agent: *\nDisallow: /\n";
		$existing_rules  = rtrim( $output );

		if ( '' === $existing_rules ) {
			return $protected_rules;
		}

		return $existing_rules . "\n" . $protected_rules;
	}

	/**
	 * Disables the WordPress XML sitemap provider while protected.
	 *
	 * @param bool $is_enabled Previously filtered sitemap state.
	 * @return bool Filtered sitemap state.
	 */
	public function filter_sitemaps_enabled( bool $is_enabled ): bool {
		$decision = $this->policy->decide();

		if ( ! $decision->is_blocked() ) {
			return $is_enabled;
		}

		$this->emit_once( $decision );

		return false;
	}

	/**
	 * Emits one privacy-safe indexing event per request.
	 *
	 * @param Decision $decision Active indexing decision.
	 */
	private function emit_once( Decision $decision ): void {
		if ( $this->decision_emitted ) {
			return;
		}

		$this->event_emitter->emit( 'indexing', $decision );
		$this->decision_emitted = true;
	}
}
