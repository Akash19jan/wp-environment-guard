<?php
/**
 * Tests WooCommerce webhook decisions.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Policy\Webhook_Policy;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Verifies webhook decisions.
 */
#[CoversClass( Webhook_Policy::class )]
final class Webhook_Policy_Test extends Policy_Test_Case {

	/**
	 * Secure defaults block delivery on staging.
	 */
	public function test_staging_webhooks_are_blocked_by_default(): void {
		$decision = ( new Webhook_Policy( $this->environment(), $this->settings() ) )->decide();

		self::assertTrue( $decision->is_blocked() );
		self::assertSame( 'webhooks_blocked', $decision->reason() );
	}

	/**
	 * Delivery can be explicitly allowed on a guarded environment.
	 */
	public function test_disabled_protection_allows_webhooks(): void {
		$decision = ( new Webhook_Policy(
			$this->environment(),
			$this->settings( array( 'block_webhooks' => false ) )
		) )->decide();

		self::assertTrue( $decision->is_allowed() );
		self::assertSame( 'webhooks_explicitly_allowed', $decision->reason() );
	}

	/**
	 * Production ignores restrictive webhook settings.
	 */
	public function test_production_is_always_allowed(): void {
		$decision = ( new Webhook_Policy(
			$this->environment( Environment_Context::TYPE_PRODUCTION ),
			$this->settings()
		) )->decide();

		self::assertTrue( $decision->is_allowed() );
		self::assertSame( 'production_inert', $decision->reason() );
	}
}
