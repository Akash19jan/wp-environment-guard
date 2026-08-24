<?php
/**
 * Decides WooCommerce webhook delivery.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Policy;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;

/**
 * Pure WooCommerce webhook policy.
 */
final readonly class Webhook_Policy {

	/**
	 * Creates the webhook policy.
	 *
	 * @param Environment_Context $environment Current environment.
	 * @param Settings            $settings    Validated settings.
	 */
	public function __construct(
		private Environment_Context $environment,
		private Settings $settings
	) {}

	/**
	 * Decides whether WooCommerce webhooks may be delivered.
	 */
	public function decide(): Decision {
		if ( ! $this->environment->is_guarded() ) {
			return Decision::allow( 'production_inert' );
		}

		if ( ! $this->settings->block_webhooks() ) {
			return Decision::allow( 'webhooks_explicitly_allowed' );
		}

		return Decision::block( 'webhooks_blocked' );
	}
}
