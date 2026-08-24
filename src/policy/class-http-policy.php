<?php
/**
 * Decides whether a WordPress HTTP API request may proceed.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Policy;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use Akash_Aagrawal\Environment_Guard\Support\Host_Matcher;

/**
 * Pure outbound HTTP policy.
 */
final readonly class HTTP_Policy {

	/**
	 * Creates the outbound HTTP policy.
	 *
	 * @param Environment_Context $environment  Current environment.
	 * @param Settings            $settings     Validated settings.
	 * @param Host_Matcher        $host_matcher Exact hostname matcher.
	 */
	public function __construct(
		private Environment_Context $environment,
		private Settings $settings,
		private Host_Matcher $host_matcher
	) {}

	/**
	 * Decides whether an outbound request may proceed.
	 *
	 * @param string $url Requested URL.
	 */
	public function decide( string $url ): Decision {
		if ( ! $this->environment->is_guarded() ) {
			return Decision::allow( 'production_inert' );
		}

		if ( Settings::HTTP_MODE_OBSERVE === $this->settings->http_mode() ) {
			return Decision::observe( 'http_observed' );
		}

		if ( $this->host_matcher->is_allowed_url( $url ) ) {
			return Decision::allow( 'http_host_allowed' );
		}

		return Decision::block( 'http_host_blocked' );
	}

	/**
	 * Returns whether redirects must be disabled to prevent host escapes.
	 */
	public function prevents_redirects(): bool {
		return $this->environment->is_guarded()
			&& Settings::HTTP_MODE_ALLOWLIST === $this->settings->http_mode();
	}
}
