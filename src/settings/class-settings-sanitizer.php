<?php
/**
 * Validates settings submitted through the WordPress Settings API.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Settings;

/**
 * Converts untrusted settings input into a strict option array.
 */
final class Settings_Sanitizer {

	/**
	 * Sanitizes the complete settings payload.
	 *
	 * @param mixed $submitted Untrusted Settings API value.
	 * @return array<string, bool|string|list<string>> Sanitized settings.
	 */
	public function sanitize( mixed $submitted ): array {
		$submitted = is_array( $submitted ) ? $submitted : array();

		$mail_mode      = $this->allowed_value(
			$submitted['mail_mode'] ?? '',
			array(
				Settings::MAIL_MODE_ALLOW,
				Settings::MAIL_MODE_BLOCK,
				Settings::MAIL_MODE_REDIRECT,
			),
			Settings::MAIL_MODE_BLOCK
		);
		$redirect_value = $submitted['mail_redirect_address'] ?? '';
		$redirect       = is_scalar( $redirect_value ) ? sanitize_email( (string) $redirect_value ) : '';

		if ( Settings::MAIL_MODE_REDIRECT === $mail_mode && ! is_email( $redirect ) ) {
			$mail_mode = Settings::MAIL_MODE_BLOCK;
			$redirect  = '';

			add_settings_error(
				Settings_Repository::OPTION_NAME,
				'akash_aagrawal_environment_guard_invalid_redirect',
				esc_html__( 'Mail mode was changed to Block because the redirect address was invalid.', 'wp-environment-guard' )
			);
		}

		return array(
			'show_frontend_banner'     => ! empty( $submitted['show_frontend_banner'] ),
			'protect_indexing'         => ! empty( $submitted['protect_indexing'] ),
			'mail_mode'                => $mail_mode,
			'mail_redirect_address'    => $redirect,
			'http_mode'                => $this->allowed_value(
				$submitted['http_mode'] ?? '',
				array( Settings::HTTP_MODE_OBSERVE, Settings::HTTP_MODE_ALLOWLIST ),
				Settings::HTTP_MODE_OBSERVE
			),
			'http_allowed_hosts'       => $this->sanitize_hosts( $submitted['http_allowed_hosts'] ?? '' ),
			'payment_mode'             => $this->allowed_value(
				$submitted['payment_mode'] ?? '',
				array(
					Settings::PAYMENT_MODE_ALLOW,
					Settings::PAYMENT_MODE_BLOCK,
					Settings::PAYMENT_MODE_ALLOWLIST,
				),
				Settings::PAYMENT_MODE_ALLOWLIST
			),
			'payment_allowed_gateways' => $this->sanitize_keys( $submitted['payment_allowed_gateways'] ?? '' ),
			'block_webhooks'           => ! empty( $submitted['block_webhooks'] ),
		);
	}

	/**
	 * Selects a value from a strict allowlist.
	 *
	 * @param mixed  $candidate Candidate value.
	 * @param array  $allowed   Allowed values.
	 * @param string $fallback  Safe fallback.
	 * @phpstan-param list<string> $allowed Allowed values.
	 */
	private function allowed_value( mixed $candidate, array $allowed, string $fallback ): string {
		$candidate = is_string( $candidate ) ? $candidate : '';

		return in_array( $candidate, $allowed, true ) ? $candidate : $fallback;
	}

	/**
	 * Sanitizes an exact hostname list.
	 *
	 * @param mixed $value Newline- or comma-delimited hosts.
	 * @return list<string> Valid ASCII hosts and IP addresses.
	 */
	private function sanitize_hosts( mixed $value ): array {
		$hosts = $this->split_lines( $value );
		$valid = array();

		foreach ( $hosts as $host ) {
			$host = strtolower( rtrim( sanitize_text_field( $host ), '.' ) );

			if ( $this->is_valid_host( $host ) ) {
				$valid[] = $host;
			}
		}

		return array_values( array_unique( $valid ) );
	}

	/**
	 * Sanitizes a list of WordPress-style identifiers.
	 *
	 * @param mixed $value Newline- or comma-delimited keys.
	 * @return list<string> Sanitized keys.
	 */
	private function sanitize_keys( mixed $value ): array {
		$keys = array_map( 'sanitize_key', $this->split_lines( $value ) );
		$keys = array_filter( $keys );

		return array_values( array_unique( $keys ) );
	}

	/**
	 * Splits a textarea value into trimmed lines.
	 *
	 * @param mixed $value Submitted value.
	 * @return list<string> Non-empty lines.
	 */
	private function split_lines( mixed $value ): array {
		if ( is_array( $value ) ) {
			$scalar_values = array();

			foreach ( $value as $item ) {
				if ( is_scalar( $item ) ) {
					$scalar_values[] = (string) $item;
				}
			}

			$value = implode( "\n", $scalar_values );
		}

		if ( ! is_scalar( $value ) ) {
			$value = '';
		}

		$parts = preg_split( '/[\r\n,]+/', (string) $value );
		$parts = is_array( $parts ) ? array_map( 'trim', $parts ) : array();

		return array_values( array_filter( $parts ) );
	}

	/**
	 * Validates an exact host without accepting URLs or wildcard suffixes.
	 *
	 * @param string $host Candidate host.
	 */
	private function is_valid_host( string $host ): bool {
		if ( '' === $host ) {
			return false;
		}

		if ( false !== filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return true;
		}

		return 1 === preg_match(
			'/\A(?=.{1,253}\z)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\z/',
			$host
		);
	}
}
