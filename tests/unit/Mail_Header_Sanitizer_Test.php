<?php
/**
 * Tests redirected mail-header sanitization.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Support\Mail_Header_Sanitizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Verifies recipient-bearing headers are removed without destroying safe headers.
 */
#[CoversClass( Mail_Header_Sanitizer::class )]
final class Mail_Header_Sanitizer_Test extends TestCase {

	/**
	 * Newline-delimited headers lose copy and reply destinations.
	 */
	public function test_string_headers_remove_sensitive_destinations(): void {
		$headers = implode(
			"\r\n",
			array(
				'Content-Type: text/html',
				'Cc: copied@example.test',
				'Bcc: hidden@example.test',
				'Reply-To: reply@example.test',
				'X-Application: test-suite',
			)
		);

		$sanitized = ( new Mail_Header_Sanitizer() )->sanitize( $headers );

		self::assertSame(
			"Content-Type: text/html\nX-Application: test-suite",
			$sanitized
		);
	}

	/**
	 * Continuation lines belonging to a blocked header are removed too.
	 */
	public function test_folded_sensitive_header_is_removed_as_one_unit(): void {
		$headers = "Cc: first@example.test,\n second@example.test\nX-Trace: retained";

		$sanitized = ( new Mail_Header_Sanitizer() )->sanitize( $headers );

		self::assertSame( 'X-Trace: retained', $sanitized );
	}

	/**
	 * Indented recipient headers are treated as headers, not safe continuations.
	 */
	public function test_whitespace_prefixed_recipient_headers_are_removed(): void {
		$headers = "Content-Type: text/plain\n Bcc: hidden@example.test\nX-After-Bcc: retained\n\tCc: copied@example.test\nX-After-Cc: retained\n Reply-To: reply@example.test\nX-Trace: retained";

		$sanitized = ( new Mail_Header_Sanitizer() )->sanitize( $headers );

		self::assertSame(
			"Content-Type: text/plain\nX-After-Bcc: retained\nX-After-Cc: retained\nX-Trace: retained",
			$sanitized
		);
	}

	/**
	 * Colon-bearing folded lines remain part of the blocked parent header.
	 */
	public function test_colon_bearing_blocked_header_continuation_is_removed(): void {
		$headers = "Bcc: first@example.test\n X-Original-Recipient: second@example.test\n\tmetadata: third@example.test\nContent-Type: text/plain";

		$sanitized = ( new Mail_Header_Sanitizer() )->sanitize( $headers );

		self::assertSame( 'Content-Type: text/plain', $sanitized );
	}

	/**
	 * Indexed and associative header arrays preserve safe representation.
	 */
	public function test_array_headers_preserve_safe_values_and_keys(): void {
		$headers = array(
			'Content-Type: text/plain',
			'Cc: copied@example.test',
			'Reply-To' => 'reply@example.test',
			'X-Trace'  => 'safe-value',
			42         => array( 'opaque-value' ),
		);

		$sanitized = ( new Mail_Header_Sanitizer() )->sanitize( $headers );

		self::assertSame(
			array(
				0         => 'Content-Type: text/plain',
				'X-Trace' => 'safe-value',
				42        => array( 'opaque-value' ),
			),
			$sanitized
		);
	}
}
