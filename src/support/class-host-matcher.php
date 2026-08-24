<?php
/**
 * Performs exact, normalized HTTP host matching.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Support;

/**
 * Matches URL hosts without unsafe suffix or substring comparisons.
 */
final readonly class Host_Matcher {

	/**
	 * Creates a host matcher.
	 *
	 * @param array $allowed_hosts Exact allowed hosts.
	 * @phpstan-param list<string> $allowed_hosts Exact allowed hosts.
	 */
	public function __construct( private array $allowed_hosts ) {}

	/**
	 * Returns whether a URL has an explicitly allowed host.
	 *
	 * @param string $url Candidate URL.
	 */
	public function is_allowed_url( string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			return false;
		}

		$host = strtolower( rtrim( $host, '.' ) );

		return in_array( $host, $this->allowed_hosts, true );
	}
}
