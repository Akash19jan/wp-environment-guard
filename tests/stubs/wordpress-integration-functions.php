<?php
/**
 * WordPress integration function shims for isolated unit tests.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Integration;

/**
 * Returns untranslated test text.
 *
 * @param string $text   Source text.
 * @param string $domain Translation domain.
 */
function __( string $text, string $domain = 'default' ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Mirrors the WordPress translation signature.
	return $text;
}

/**
 * Returns the configured administrative-request state.
 */
function is_admin(): bool {
	return true === ( $GLOBALS['akash_aagrawal_environment_guard_test_is_admin'] ?? false );
}

/**
 * Returns the configured WordPress AJAX-request state.
 */
function wp_doing_ajax(): bool {
	return true === ( $GLOBALS['akash_aagrawal_environment_guard_test_doing_ajax'] ?? false );
}
