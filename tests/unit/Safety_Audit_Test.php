<?php
/**
 * Tests the deployment safety audit.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Policy\Mail_Policy;
use Akash_Aagrawal\Environment_Guard\Policy\Safety_Audit;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Verifies privacy-safe invariant reporting.
 */
#[CoversClass( Safety_Audit::class )]
final class Safety_Audit_Test extends Policy_Test_Case {

	/**
	 * Secure defaults pass in a guarded environment.
	 */
	public function test_secure_defaults_pass(): void {
		$audit = $this->audit( $this->environment(), $this->settings() );

		self::assertSame( array(), $audit->failures() );
		self::assertFalse( $audit->has_failures() );
	}

	/**
	 * Permissive behavior produces stable codes without configuration values.
	 */
	public function test_permissive_settings_return_stable_failure_codes(): void {
		$settings = $this->settings(
			array(
				'protect_indexing' => false,
				'mail_mode'        => Settings::MAIL_MODE_ALLOW,
				'payment_mode'     => Settings::PAYMENT_MODE_ALLOW,
				'block_webhooks'   => false,
			)
		);
		$audit    = $this->audit( $this->environment(), $settings );

		self::assertSame(
			array(
				Safety_Audit::FAILURE_INDEXING_ALLOWED,
				Safety_Audit::FAILURE_MAIL_ALLOWED,
				Safety_Audit::FAILURE_PAYMENTS_ALLOWED,
				Safety_Audit::FAILURE_WEBHOOKS_ALLOWED,
			),
			$audit->failures()
		);
	}

	/**
	 * Invalid redirect configuration is reported while mail still fails safe.
	 */
	public function test_invalid_redirect_is_reported(): void {
		$settings = $this->settings(
			array(
				'mail_mode'             => Settings::MAIL_MODE_REDIRECT,
				'mail_redirect_address' => 'not-an-address',
			)
		);
		$audit    = $this->audit( $this->environment(), $settings );

		self::assertSame( array( Safety_Audit::FAILURE_MAIL_REDIRECT_INVALID ), $audit->failures() );
	}

	/**
	 * Production remains healthy regardless of stored non-production settings.
	 */
	public function test_production_is_always_healthy(): void {
		$settings = $this->settings(
			array(
				'protect_indexing' => false,
				'mail_mode'        => Settings::MAIL_MODE_ALLOW,
			)
		);
		$audit    = $this->audit(
			$this->environment( Environment_Context::TYPE_PRODUCTION ),
			$settings
		);

		self::assertSame( array(), $audit->failures() );
	}

	/**
	 * Creates an audit using the real mail policy.
	 *
	 * @param Environment_Context $environment Environment context.
	 * @param Settings            $settings    Validated settings.
	 */
	private function audit( Environment_Context $environment, Settings $settings ): Safety_Audit {
		return new Safety_Audit(
			$environment,
			$settings,
			new Mail_Policy( $environment, $settings )
		);
	}
}
