<?php
/**
 * Integrates the webhook delivery policy with WooCommerce.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Integration;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use Akash_Aagrawal\Environment_Guard\Policy\Webhook_Policy;
use Akash_Aagrawal\Environment_Guard\Support\Policy_Event_Emitter;

/**
 * Prevents WooCommerce webhook delivery in guarded environments.
 */
final class WooCommerce_Webhook_Guard implements Hookable {

	/**
	 * Creates the WooCommerce webhook adapter.
	 *
	 * @param Webhook_Policy       $policy        Webhook policy.
	 * @param Policy_Event_Emitter $event_emitter Privacy-safe event emitter.
	 */
	public function __construct(
		private readonly Webhook_Policy $policy,
		private readonly Policy_Event_Emitter $event_emitter
	) {}

	/**
	 * Registers the WooCommerce webhook delivery filter.
	 */
	public function register_hooks(): void {
		add_filter( 'woocommerce_webhook_should_deliver', array( $this, 'filter_should_deliver' ), PHP_INT_MAX, 1 );
	}

	/**
	 * Applies webhook policy while preserving an earlier block decision.
	 *
	 * @param bool $should_deliver Previously filtered delivery state.
	 * @return bool Filtered delivery state.
	 */
	public function filter_should_deliver( bool $should_deliver ): bool {
		if ( ! $should_deliver ) {
			return false;
		}

		$decision = $this->policy->decide();

		if ( ! $decision->is_blocked() ) {
			return true;
		}

		$this->event_emitter->emit( 'webhook', $decision );

		return false;
	}
}
