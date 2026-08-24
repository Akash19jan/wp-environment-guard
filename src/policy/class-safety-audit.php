<?php
/**
 * Audits high-impact environment guard invariants.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Policy;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;

/**
 * Produces privacy-safe failure codes for deployment and Site Health checks.
 */
final readonly class Safety_Audit {

	public const FAILURE_EMERGENCY_DISABLED = 'emergency_disabled';

	public const FAILURE_INDEXING_ALLOWED = 'indexing_allowed';

	public const FAILURE_MAIL_ALLOWED = 'mail_allowed';

	public const FAILURE_MAIL_REDIRECT_INVALID = 'mail_redirect_invalid';

	public const FAILURE_PAYMENTS_ALLOWED = 'payments_allowed';

	public const FAILURE_WEBHOOKS_ALLOWED = 'webhooks_allowed';

	/**
	 * Creates the safety audit.
	 *
	 * @param Environment_Context $environment Current environment.
	 * @param Settings            $settings    Validated settings.
	 * @param Mail_Policy         $mail_policy Effective mail policy.
	 */
	public function __construct(
		private Environment_Context $environment,
		private Settings $settings,
		private Mail_Policy $mail_policy
	) {}

	/**
	 * Returns stable, privacy-safe failure codes.
	 *
	 * Production is intentionally healthy because every policy is inert there.
	 *
	 * @return list<string> Failed safety invariants.
	 */
	public function failures(): array {
		if ( $this->environment->is_production() ) {
			return array();
		}

		if ( $this->environment->is_emergency_disabled() ) {
			return array( self::FAILURE_EMERGENCY_DISABLED );
		}

		$failures = array();

		if ( ! $this->settings->protect_indexing() ) {
			$failures[] = self::FAILURE_INDEXING_ALLOWED;
		}

		if ( Settings::MAIL_MODE_ALLOW === $this->settings->mail_mode() ) {
			$failures[] = self::FAILURE_MAIL_ALLOWED;
		}

		if ( 'mail_redirect_missing' === $this->mail_policy->decide()->reason() ) {
			$failures[] = self::FAILURE_MAIL_REDIRECT_INVALID;
		}

		if ( Settings::PAYMENT_MODE_ALLOW === $this->settings->payment_mode() ) {
			$failures[] = self::FAILURE_PAYMENTS_ALLOWED;
		}

		if ( ! $this->settings->block_webhooks() ) {
			$failures[] = self::FAILURE_WEBHOOKS_ALLOWED;
		}

		return $failures;
	}

	/**
	 * Returns whether any safety invariant failed.
	 */
	public function has_failures(): bool {
		return array() !== $this->failures();
	}
}
