<?php
/**
 * Tests the WooCommerce webhook adapter.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Integration\WooCommerce_Webhook_Guard;
use Akash_Aagrawal\Environment_Guard\Policy\Webhook_Policy;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use Akash_Aagrawal\Environment_Guard\Support\Policy_Event_Emitter;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Verifies webhook delivery filtering and earlier-veto preservation.
 */
#[CoversClass( WooCommerce_Webhook_Guard::class )]
final class WooCommerce_Webhook_Guard_Test extends Policy_Test_Case {

	/**
	 * Clears captured policy actions before each test.
	 */
	protected function setUp(): void {
		$this->reset_test_state();
	}

	/**
	 * Clears captured policy actions after each test.
	 */
	protected function tearDown(): void {
		$this->reset_test_state();
	}

	/**
	 * An earlier false decision remains false without emitting another event.
	 */
	public function test_preserves_an_earlier_false_decision(): void {
		$guard = $this->guard( $this->environment(), $this->settings() );

		self::assertFalse( $guard->filter_should_deliver( false ) );
		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_action',
			$GLOBALS
		);
	}

	/**
	 * Guarded environments block webhook delivery with safe event metadata.
	 */
	public function test_guarded_environment_blocks_delivery(): void {
		$guard = $this->guard( $this->environment(), $this->settings() );

		self::assertFalse( $guard->filter_should_deliver( true ) );
		self::assertSame(
			array(
				'hook' => 'akash_aagrawal_environment_guard_policy_decision',
				'args' => array( 'webhook', 'block', 'webhooks_blocked' ),
			),
			$GLOBALS['akash_aagrawal_environment_guard_test_action']
		);
	}

	/**
	 * Production preserves an affirmative delivery decision.
	 */
	public function test_production_is_inert(): void {
		$guard = $this->guard(
			$this->environment( Environment_Context::TYPE_PRODUCTION ),
			$this->settings()
		);

		self::assertTrue( $guard->filter_should_deliver( true ) );
		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_action',
			$GLOBALS
		);
	}

	/**
	 * An explicit guarded-environment opt-out permits delivery.
	 */
	public function test_disabled_protection_allows_delivery(): void {
		$guard = $this->guard(
			$this->environment(),
			$this->settings( array( 'block_webhooks' => false ) )
		);

		self::assertTrue( $guard->filter_should_deliver( true ) );
		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_action',
			$GLOBALS
		);
	}

	/**
	 * Creates the webhook adapter with its real policy.
	 *
	 * @param Environment_Context $environment Current environment.
	 * @param Settings            $settings    Validated settings.
	 */
	private function guard(
		Environment_Context $environment,
		Settings $settings
	): WooCommerce_Webhook_Guard {
		return new WooCommerce_Webhook_Guard(
			new Webhook_Policy( $environment, $settings ),
			new Policy_Event_Emitter()
		);
	}

	/**
	 * Clears action-capture state used by the isolated hook shim.
	 */
	private function reset_test_state(): void {
		unset(
			$GLOBALS['akash_aagrawal_environment_guard_test_action'],
			$GLOBALS['akash_aagrawal_environment_guard_test_actions']
		);
	}
}
