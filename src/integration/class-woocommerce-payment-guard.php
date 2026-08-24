<?php
/**
 * Integrates the payment gateway policy with WooCommerce.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Integration;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use Akash_Aagrawal\Environment_Guard\Policy\Gateway_Policy;
use Akash_Aagrawal\Environment_Guard\Support\Policy_Event_Emitter;

/**
 * Removes payment gateways that are unsafe in guarded environments.
 */
final class WooCommerce_Payment_Guard implements Hookable {

	/**
	 * Creates the WooCommerce payment adapter.
	 *
	 * @param Gateway_Policy       $policy        Payment gateway policy.
	 * @param Policy_Event_Emitter $event_emitter Privacy-safe event emitter.
	 */
	public function __construct(
		private readonly Gateway_Policy $policy,
		private readonly Policy_Event_Emitter $event_emitter
	) {}

	/**
	 * Registers the WooCommerce payment gateway filter.
	 */
	public function register_hooks(): void {
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_gateways' ), PHP_INT_MAX );
	}

	/**
	 * Removes blocked gateways from the list already filtered by WooCommerce.
	 *
	 * Administrative screens are left untouched; AJAX checkout requests remain
	 * protected because WooCommerce calculates gateways during those requests.
	 *
	 * @param array<string, mixed> $gateways Available payment gateways.
	 * @return array<string, mixed> Filtered payment gateways.
	 */
	public function filter_gateways( array $gateways ): array {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $gateways;
		}

		foreach ( array_keys( $gateways ) as $gateway_id ) {
			$decision = $this->policy->decide( (string) $gateway_id );

			if ( ! $decision->is_blocked() ) {
				continue;
			}

			unset( $gateways[ $gateway_id ] );
			$this->event_emitter->emit( 'payment', $decision );
		}

		return $gateways;
	}
}
