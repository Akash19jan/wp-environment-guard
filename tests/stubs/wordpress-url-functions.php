<?php
/**
 * WordPress URL function shims for isolated unit tests.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Support;

/**
 * Minimal WordPress-compatible URL parser for isolated unit tests.
 *
 * @param string $url       URL to parse.
 * @param int    $component PHP_URL_* component constant.
 * @return array<string, int|string>|int|string|null|false Parsed URL value.
 */
function wp_parse_url( string $url, int $component = -1 ): array|int|string|null|false {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- WordPress is intentionally absent from isolated unit tests.
	return parse_url( $url, $component );
}
