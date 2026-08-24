<?php
/**
 * Tests outbound mail decisions.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Policy\Mail_Policy;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Verifies block, redirect, allow, and production paths.
 */
#[CoversClass( Mail_Policy::class )]
final class Mail_Policy_Test extends Policy_Test_Case {

	/**
	 * Secure defaults block mail on staging.
	 */
	public function test_staging_mail_is_blocked_by_default(): void {
		$decision = ( new Mail_Policy( $this->environment(), $this->settings() ) )->decide();

		self::assertTrue( $decision->is_blocked() );
		self::assertSame( 'mail_blocked', $decision->reason() );
	}

	/**
	 * Explicit allow mode passes mail through.
	 */
	public function test_allow_mode_allows_mail(): void {
		$decision = ( new Mail_Policy(
			$this->environment(),
			$this->settings( array( 'mail_mode' => Settings::MAIL_MODE_ALLOW ) )
		) )->decide();

		self::assertTrue( $decision->is_allowed() );
		self::assertSame( 'mail_explicitly_allowed', $decision->reason() );
	}

	/**
	 * Redirect mode carries the configured safe sink.
	 */
	public function test_redirect_mode_returns_safe_sink(): void {
		$decision = ( new Mail_Policy(
			$this->environment(),
			$this->settings(
				array(
					'mail_mode'             => Settings::MAIL_MODE_REDIRECT,
					'mail_redirect_address' => 'sink@example.test',
				)
			)
		) )->decide();

		self::assertTrue( $decision->is_redirected() );
		self::assertSame( 'sink@example.test', $decision->target() );
	}

	/**
	 * A missing redirect sink fails safely to block.
	 */
	public function test_redirect_without_sink_is_blocked(): void {
		$decision = ( new Mail_Policy(
			$this->environment(),
			$this->settings( array( 'mail_mode' => Settings::MAIL_MODE_REDIRECT ) )
		) )->decide();

		self::assertTrue( $decision->is_blocked() );
		self::assertSame( 'mail_redirect_missing', $decision->reason() );
	}

	/**
	 * Production ignores restrictive mail settings.
	 */
	public function test_production_is_always_allowed(): void {
		$decision = ( new Mail_Policy(
			$this->environment( Environment_Context::TYPE_PRODUCTION ),
			$this->settings()
		) )->decide();

		self::assertTrue( $decision->is_allowed() );
		self::assertSame( 'production_inert', $decision->reason() );
	}
}
