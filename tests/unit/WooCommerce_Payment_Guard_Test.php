<?php
/**
 * Tests the WooCommerce payment-gateway adapter.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Integration\WooCommerce_Payment_Guard;
use Akash_Aagrawal\Environment_Guard\Policy\Gateway_Policy;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use Akash_Aagrawal\Environment_Guard\Support\Policy_Event_Emitter;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

/**
 * Verifies checkout filtering without changing gateway configuration.
 */
#[CoversClass( WooCommerce_Payment_Guard::class )]
final class WooCommerce_Payment_Guard_Test extends Policy_Test_Case {

	/**
	 * Resets WordPress request state and captured actions before each test.
	 */
	protected function setUp(): void {
		$this->reset_test_state();
	}

	/**
	 * Resets WordPress request state and captured actions after each test.
	 */
	protected function tearDown(): void {
		$this->reset_test_state();
	}

	/**
	 * A guarded storefront keeps allowlisted gateways and removes the rest.
	 */
	public function test_guarded_storefront_filters_gateways(): void {
		$bacs    = new stdClass();
		$stripe  = new stdClass();
		$gateway = $this->guard( $this->environment(), $this->settings() );

		$filtered = $gateway->filter_gateways(
			array(
				'bacs'   => $bacs,
				'stripe' => $stripe,
			)
		);

		self::assertSame( array( 'bacs' => $bacs ), $filtered );
		self::assertSame(
			array(
				'hook' => 'akash_aagrawal_environment_guard_policy_decision',
				'args' => array( 'payment', 'block', 'payment_gateway_not_allowed' ),
			),
			$GLOBALS['akash_aagrawal_environment_guard_test_action']
		);
		self::assertNotContains(
			'stripe',
			$GLOBALS['akash_aagrawal_environment_guard_test_action']['args']
		);
	}

	/**
	 * Ordinary admin requests preserve gateways, while AJAX checkout is guarded.
	 */
	public function test_admin_passes_through_but_ajax_is_filtered(): void {
		$gateways = array( 'stripe' => new stdClass() );
		$guard    = $this->guard( $this->environment(), $this->settings() );

		$GLOBALS['akash_aagrawal_environment_guard_test_is_admin']   = true;
		$GLOBALS['akash_aagrawal_environment_guard_test_doing_ajax'] = false;

		self::assertSame( $gateways, $guard->filter_gateways( $gateways ) );
		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_action',
			$GLOBALS
		);

		$GLOBALS['akash_aagrawal_environment_guard_test_doing_ajax'] = true;

		self::assertSame( array(), $guard->filter_gateways( $gateways ) );
		self::assertSame(
			array( 'payment', 'block', 'payment_gateway_not_allowed' ),
			$GLOBALS['akash_aagrawal_environment_guard_test_action']['args']
		);
	}

	/**
	 * Production passes all runtime gateway objects through unchanged.
	 */
	public function test_production_is_inert(): void {
		$gateways = array(
			'bacs'   => new stdClass(),
			'stripe' => new stdClass(),
		);
		$guard    = $this->guard(
			$this->environment( Environment_Context::TYPE_PRODUCTION ),
			$this->settings( array( 'payment_mode' => Settings::PAYMENT_MODE_BLOCK ) )
		);

		self::assertSame( $gateways, $guard->filter_gateways( $gateways ) );
		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_action',
			$GLOBALS
		);
	}

	/**
	 * Creates the payment adapter with its real policy.
	 *
	 * @param Environment_Context $environment Current environment.
	 * @param Settings            $settings    Validated settings.
	 */
	private function guard(
		Environment_Context $environment,
		Settings $settings
	): WooCommerce_Payment_Guard {
		return new WooCommerce_Payment_Guard(
			new Gateway_Policy( $environment, $settings ),
			new Policy_Event_Emitter()
		);
	}

	/**
	 * Clears global state used only by isolated WordPress function shims.
	 */
	private function reset_test_state(): void {
		unset(
			$GLOBALS['akash_aagrawal_environment_guard_test_action'],
			$GLOBALS['akash_aagrawal_environment_guard_test_actions'],
			$GLOBALS['akash_aagrawal_environment_guard_test_is_admin'],
			$GLOBALS['akash_aagrawal_environment_guard_test_doing_ajax']
		);
	}
}
