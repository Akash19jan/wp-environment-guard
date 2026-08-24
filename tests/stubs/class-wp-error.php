<?php
/**
 * Minimal WP_Error test double.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Mirrors the WordPress core value used by the adapter.
/**
 * Provides the WP_Error behavior exercised by isolated adapter tests.
 */
class WP_Error {

	/**
	 * Creates an error value.
	 *
	 * @param string $code    Stable error code.
	 * @param string $message Human-readable error message.
	 */
	public function __construct(
		private readonly string $code = '',
		private readonly string $message = ''
	) {}

	/**
	 * Returns the error code.
	 */
	public function get_error_code(): string {
		return $this->code;
	}

	/**
	 * Returns the error message.
	 */
	public function get_error_message(): string {
		return $this->message;
	}
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
