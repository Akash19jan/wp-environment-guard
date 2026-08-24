<?php
/**
 * Removes delivery and reply headers from redirected mail.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Support;

/**
 * Sanitizes mail headers before a message is sent to a safe sink.
 */
final class Mail_Header_Sanitizer {

	/**
	 * Header names that could add recipients or expose a reply address.
	 *
	 * @var list<string>
	 */
	private const BLOCKED_HEADER_NAMES = array(
		'bcc',
		'cc',
		'reply-to',
	);

	/**
	 * Removes sensitive headers while preserving the input representation.
	 *
	 * @param array<int|string, mixed>|string $headers WordPress mail headers.
	 * @return array<int|string, mixed>|string Sanitized headers.
	 */
	public function sanitize( array|string $headers ): array|string {
		if ( is_string( $headers ) ) {
			return $this->sanitize_string( $headers );
		}

		$sanitized_headers = array();

		foreach ( $headers as $key => $value ) {
			if ( is_string( $key ) && $this->is_blocked_header_name( $key ) ) {
				continue;
			}

			if ( ! is_string( $value ) ) {
				$sanitized_headers[ $key ] = $value;
				continue;
			}

			$sanitized_value = $this->sanitize_string( $value );

			if ( '' !== $sanitized_value ) {
				$sanitized_headers[ $key ] = $sanitized_value;
			}
		}

		return $sanitized_headers;
	}

	/**
	 * Removes blocked headers and their folded continuation lines.
	 *
	 * @param string $headers Newline-delimited mail headers.
	 * @return string Sanitized mail headers.
	 */
	private function sanitize_string( string $headers ): string {
		$lines                = preg_split( '/\r\n|\r|\n/', $headers );
		$sanitized_lines      = array();
		$discard_continuation = false;

		if ( false === $lines ) {
			return '';
		}

		foreach ( $lines as $line ) {
			$is_indented = 1 === preg_match( '/^[\t ]+/', $line );

			if ( $is_indented && $discard_continuation ) {
				continue;
			}

			$normalized_line = ltrim( $line );
			$is_header_line  = false !== strpos( $normalized_line, ':' );

			if ( $is_indented && ! $is_header_line ) {
				$sanitized_lines[] = $line;
				continue;
			}

			$discard_continuation = $this->is_blocked_header_line( $normalized_line );

			if ( ! $discard_continuation ) {
				$sanitized_lines[] = $line;
			}
		}

		return implode( "\n", $sanitized_lines );
	}

	/**
	 * Checks an associative header name.
	 *
	 * @param string $name Header name.
	 * @return bool Whether the header must be removed.
	 */
	private function is_blocked_header_name( string $name ): bool {
		return in_array( strtolower( trim( $name ) ), self::BLOCKED_HEADER_NAMES, true );
	}

	/**
	 * Checks a complete header line.
	 *
	 * @param string $line Header line.
	 * @return bool Whether the header must be removed.
	 */
	private function is_blocked_header_line( string $line ): bool {
		$separator_position = strpos( $line, ':' );

		if ( false === $separator_position ) {
			return false;
		}

		return $this->is_blocked_header_name( substr( $line, 0, $separator_position ) );
	}
}
