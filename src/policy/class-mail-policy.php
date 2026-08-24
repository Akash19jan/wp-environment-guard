<?php
/**
 * Decides how WordPress mail is handled.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Policy;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;

/**
 * Pure outbound mail policy.
 */
final readonly class Mail_Policy {

	/**
	 * Creates the mail policy.
	 *
	 * @param Environment_Context $environment Current environment.
	 * @param Settings            $settings    Validated settings.
	 */
	public function __construct(
		private Environment_Context $environment,
		private Settings $settings
	) {}

	/**
	 * Decides how an outbound email should be handled.
	 */
	public function decide(): Decision {
		if ( ! $this->environment->is_guarded() ) {
			return Decision::allow( 'production_inert' );
		}

		if ( Settings::MAIL_MODE_ALLOW === $this->settings->mail_mode() ) {
			return Decision::allow( 'mail_explicitly_allowed' );
		}

		if ( Settings::MAIL_MODE_REDIRECT === $this->settings->mail_mode() ) {
			$redirect_address = $this->settings->mail_redirect_address();

			if ( false === filter_var( $redirect_address, FILTER_VALIDATE_EMAIL ) ) {
				return Decision::block( 'mail_redirect_missing' );
			}

			return Decision::redirect(
				'mail_redirected',
				$redirect_address
			);
		}

		return Decision::block( 'mail_blocked' );
	}
}
