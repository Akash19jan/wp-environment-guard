<?php
/**
 * Tests immutable settings values.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Verifies safe defaults and normalized construction.
 */
#[CoversClass( Settings::class )]
final class Settings_Test extends TestCase {

	/**
	 * Defaults establish conservative non-production behavior.
	 */
	public function test_defaults_are_safe_for_non_production(): void {
		$settings = Settings::from_array( array() );

		self::assertTrue( $settings->show_frontend_banner() );
		self::assertTrue( $settings->protect_indexing() );
		self::assertSame( Settings::MAIL_MODE_BLOCK, $settings->mail_mode() );
		self::assertSame( '', $settings->mail_redirect_address() );
		self::assertSame( Settings::HTTP_MODE_OBSERVE, $settings->http_mode() );
		self::assertSame(
			array( 'api.wordpress.org', 'downloads.wordpress.org', 'wordpress.org' ),
			$settings->http_allowed_hosts()
		);
		self::assertSame( Settings::PAYMENT_MODE_ALLOWLIST, $settings->payment_mode() );
		self::assertSame( array( 'bacs', 'cheque', 'cod' ), $settings->payment_allowed_gateways() );
		self::assertTrue( $settings->block_webhooks() );
	}

	/**
	 * Supplied values override only their corresponding defaults.
	 */
	public function test_from_array_merges_partial_values_with_defaults(): void {
		$settings = Settings::from_array(
			array(
				'mail_mode'             => Settings::MAIL_MODE_REDIRECT,
				'mail_redirect_address' => 'sink@example.test',
				'http_allowed_hosts'    => array( 'api.example.test' ),
				'block_webhooks'        => false,
			)
		);

		self::assertSame( Settings::MAIL_MODE_REDIRECT, $settings->mail_mode() );
		self::assertSame( 'sink@example.test', $settings->mail_redirect_address() );
		self::assertSame( array( 'api.example.test' ), $settings->http_allowed_hosts() );
		self::assertFalse( $settings->block_webhooks() );
		self::assertTrue( $settings->protect_indexing() );
	}

	/**
	 * Corrupt option shapes fall back without leaking mixed values downstream.
	 */
	public function test_from_array_normalizes_untrusted_stored_values(): void {
		$settings = Settings::from_array(
			array(
				'protect_indexing'         => 'false',
				'mail_mode'                => 'unexpected',
				'http_allowed_hosts'       => array( ' API.EXAMPLE.TEST ', 123, '', 'api.example.test' ),
				'payment_allowed_gateways' => new \stdClass(),
				'block_webhooks'           => 'no',
			)
		);

		self::assertTrue( $settings->protect_indexing() );
		self::assertSame( Settings::MAIL_MODE_BLOCK, $settings->mail_mode() );
		self::assertSame( array( 'api.example.test' ), $settings->http_allowed_hosts() );
		self::assertSame( array( 'bacs', 'cheque', 'cod' ), $settings->payment_allowed_gateways() );
		self::assertTrue( $settings->block_webhooks() );
	}
}
