<?php
/**
 * Decides WooCommerce gateway availability.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Policy;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;

/**
 * Pure WooCommerce payment policy.
 */
final readonly class Gateway_Policy {

	/**
	 * Creates the payment gateway policy.
	 *
	 * @param Environment_Context $environment Current environment.
	 * @param Settings            $settings    Validated settings.
	 */
	public function __construct(
		private Environment_Context $environment,
		private Settings $settings
	) {}

	/**
	 * Decides whether a WooCommerce gateway should be available.
	 *
	 * @param string $gateway_id WooCommerce gateway identifier.
	 */
	public function decide( string $gateway_id ): Decision {
		if ( ! $this->environment->is_guarded() ) {
			return Decision::allow( 'production_inert' );
		}

		if ( Settings::PAYMENT_MODE_ALLOW === $this->settings->payment_mode() ) {
			return Decision::allow( 'payments_explicitly_allowed' );
		}

		if ( Settings::PAYMENT_MODE_BLOCK === $this->settings->payment_mode() ) {
			return Decision::block( 'payments_blocked' );
		}

		if ( in_array( $gateway_id, $this->settings->payment_allowed_gateways(), true ) ) {
			return Decision::allow( 'payment_gateway_allowed' );
		}

		return Decision::block( 'payment_gateway_not_allowed' );
	}
}
