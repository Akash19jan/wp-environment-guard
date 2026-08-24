<?php
/**
 * Tests WooCommerce gateway decisions.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Policy\Gateway_Policy;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Verifies gateway availability decisions without mutating gateway settings.
 */
#[CoversClass( Gateway_Policy::class )]
final class Gateway_Policy_Test extends Policy_Test_Case {

	/**
	 * Secure defaults allow only explicitly safe gateway identifiers.
	 */
	public function test_default_allowlist_distinguishes_gateway_ids(): void {
		$policy = new Gateway_Policy( $this->environment(), $this->settings() );

		self::assertTrue( $policy->decide( 'bacs' )->is_allowed() );
		self::assertSame( 'payment_gateway_allowed', $policy->decide( 'cod' )->reason() );
		self::assertTrue( $policy->decide( 'stripe' )->is_blocked() );
		self::assertSame( 'payment_gateway_not_allowed', $policy->decide( 'stripe' )->reason() );
	}

	/**
	 * Block mode rejects every gateway identifier.
	 */
	public function test_block_mode_rejects_every_gateway(): void {
		$policy = new Gateway_Policy(
			$this->environment(),
			$this->settings( array( 'payment_mode' => Settings::PAYMENT_MODE_BLOCK ) )
		);

		self::assertTrue( $policy->decide( 'cod' )->is_blocked() );
		self::assertSame( 'payments_blocked', $policy->decide( 'cod' )->reason() );
	}

	/**
	 * Explicit allow mode passes every gateway through.
	 */
	public function test_allow_mode_accepts_every_gateway(): void {
		$policy = new Gateway_Policy(
			$this->environment(),
			$this->settings( array( 'payment_mode' => Settings::PAYMENT_MODE_ALLOW ) )
		);

		self::assertTrue( $policy->decide( 'live-gateway' )->is_allowed() );
		self::assertSame( 'payments_explicitly_allowed', $policy->decide( 'live-gateway' )->reason() );
	}

	/**
	 * Production ignores restrictive payment settings.
	 */
	public function test_production_is_always_allowed(): void {
		$policy = new Gateway_Policy(
			$this->environment( Environment_Context::TYPE_PRODUCTION ),
			$this->settings( array( 'payment_mode' => Settings::PAYMENT_MODE_BLOCK ) )
		);

		self::assertTrue( $policy->decide( 'live-gateway' )->is_allowed() );
		self::assertSame( 'production_inert', $policy->decide( 'live-gateway' )->reason() );
	}
}
