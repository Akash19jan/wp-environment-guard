<?php
/**
 * Integrates the outbound HTTP policy with WordPress.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Integration;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use Akash_Aagrawal\Environment_Guard\Policy\HTTP_Policy;
use Akash_Aagrawal\Environment_Guard\Support\Policy_Event_Emitter;
use WP_Error;

/**
 * Observes or blocks requests made through the WordPress HTTP API.
 */
final class HTTP_Guard implements Hookable {

	/**
	 * Creates the WordPress HTTP API adapter.
	 *
	 * @param HTTP_Policy          $policy        HTTP policy.
	 * @param Policy_Event_Emitter $event_emitter Privacy-safe event emitter.
	 */
	public function __construct(
		private readonly HTTP_Policy $policy,
		private readonly Policy_Event_Emitter $event_emitter
	) {}

	/**
	 * Registers WordPress HTTP API filters.
	 */
	public function register_hooks(): void {
		// This filter changes only redirects; it never increases the HTTP timeout.
		// phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.http_request_args
		add_filter( 'http_request_args', array( $this, 'filter_request_arguments' ), PHP_INT_MAX, 1 );
		add_filter( 'pre_http_request', array( $this, 'preempt_request' ), PHP_INT_MAX, 3 );
	}

	/**
	 * Prevents redirect hops from escaping host enforcement.
	 *
	 * @param array<string, mixed> $arguments Existing request arguments.
	 * @return array<string, mixed> Filtered request arguments.
	 */
	public function filter_request_arguments( array $arguments ): array {
		if ( $this->policy->prevents_redirects() ) {
			$arguments['redirection'] = 0;
		}

		return $arguments;
	}

	/**
	 * Applies the outbound request decision without exposing request details.
	 *
	 * @param false|array<string, mixed>|WP_Error $preempt  Earlier short-circuit value.
	 * @param array<string, mixed>                $arguments Request arguments, intentionally unused.
	 * @param string                              $url       Request URL.
	 * @return false|array<string, mixed>|WP_Error Filtered short-circuit value.
	 */
	public function preempt_request( false|array|WP_Error $preempt, array $arguments, string $url ): false|array|WP_Error { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The WordPress filter supplies request arguments before the URL.
		if ( false !== $preempt ) {
			return $preempt;
		}

		$decision = $this->policy->decide( $url );

		if ( $decision->is_allowed() ) {
			return false;
		}

		$this->event_emitter->emit( 'http', $decision );

		if ( ! $decision->is_blocked() ) {
			return false;
		}

		return new WP_Error(
			'akash_aagrawal_environment_guard_http_blocked',
			__( 'Outbound request blocked by the environment policy.', 'wp-environment-guard' )
		);
	}
}
