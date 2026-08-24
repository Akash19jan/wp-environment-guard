<?php
/**
 * Decides whether public indexing must be blocked.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Policy;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;

/**
 * Pure indexing policy.
 */
final readonly class Indexing_Policy {

	/**
	 * Creates the indexing policy.
	 *
	 * @param Environment_Context $environment Current environment.
	 * @param Settings            $settings    Validated settings.
	 */
	public function __construct(
		private Environment_Context $environment,
		private Settings $settings
	) {}

	/**
	 * Decides whether indexing should be blocked.
	 */
	public function decide(): Decision {
		if ( ! $this->environment->is_guarded() ) {
			return Decision::allow( 'production_inert' );
		}

		if ( ! $this->settings->protect_indexing() ) {
			return Decision::allow( 'indexing_policy_disabled' );
		}

		return Decision::block( 'non_production_indexing' );
	}
}
