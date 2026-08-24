<?php
/**
 * Exposes environment guard status through WordPress Site Health.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Integration;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Policy\Safety_Audit;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;

/**
 * Adds a privacy-safe Site Health test and debug information section.
 */
final class Site_Health implements Hookable {

	/**
	 * Creates the Site Health adapter.
	 *
	 * @param Environment_Context $environment Current environment.
	 * @param Settings            $settings    Validated plugin settings.
	 * @param Safety_Audit        $safety_audit Privacy-safe safety audit.
	 */
	public function __construct(
		private readonly Environment_Context $environment,
		private readonly Settings $settings,
		private readonly Safety_Audit $safety_audit
	) {}

	/**
	 * Registers Site Health filters.
	 */
	public function register_hooks(): void {
		add_filter( 'site_status_tests', array( $this, 'register_status_test' ) );
		add_filter( 'debug_information', array( $this, 'add_debug_information' ) );
	}

	/**
	 * Registers the direct environment guard status test.
	 *
	 * @param array<string, mixed> $tests Registered Site Health tests.
	 * @return array<string, mixed> Filtered Site Health tests.
	 */
	public function register_status_test( array $tests ): array {
		if ( ! isset( $tests['direct'] ) || ! is_array( $tests['direct'] ) ) {
			$tests['direct'] = array();
		}

		$tests['direct']['akash_aagrawal_environment_guard'] = array(
			'label' => __( 'Environment Guard status', 'wp-environment-guard' ),
			'test'  => array( $this, 'get_status_test' ),
		);

		return $tests;
	}

	/**
	 * Returns the environment guard Site Health result.
	 *
	 * @return array<string, mixed> Site Health test result.
	 */
	public function get_status_test(): array {
		$result = array(
			'label'       => __( 'Environment Guard safeguards are active', 'wp-environment-guard' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Environment Guard', 'wp-environment-guard' ),
				'color' => 'blue',
			),
			'description' => '<p>' . esc_html__( 'Non-production protections are active for this environment.', 'wp-environment-guard' ) . '</p>',
			'actions'     => '',
			'test'        => 'akash_aagrawal_environment_guard',
		);

		if ( $this->environment->is_production() ) {
			$result['label']       = __( 'Environment Guard is inert in production', 'wp-environment-guard' );
			$result['description'] = '<p>' . esc_html__( 'WordPress reports a production environment, so the plugin is not changing site behavior.', 'wp-environment-guard' ) . '</p>';

			return $result;
		}

		if ( $this->environment->is_emergency_disabled() ) {
			$result['label']       = __( 'Environment Guard protections are disabled', 'wp-environment-guard' );
			$result['status']      = 'recommended';
			$result['description'] = '<p>' . esc_html__( 'The emergency-disable constant is active. Remove it when the temporary bypass is no longer required.', 'wp-environment-guard' ) . '</p>';

			return $result;
		}

		if ( $this->safety_audit->has_failures() ) {
			$result['label']       = __( 'Some Environment Guard safeguards are permissive', 'wp-environment-guard' );
			$result['status']      = 'recommended';
			$result['description'] = '<p>' . esc_html__( 'One or more high-impact safeguards allow indexing, email, payments, or webhooks. Review the Environment Guard settings for this non-production site.', 'wp-environment-guard' ) . '</p>';
		}

		return $result;
	}

	/**
	 * Adds nonsensitive configuration values to Site Health information.
	 *
	 * Redirect addresses, allowed hosts, gateway identifiers, URLs, request
	 * headers, and payloads are deliberately excluded.
	 *
	 * @param array<string, mixed> $information Existing debug information.
	 * @return array<string, mixed> Filtered debug information.
	 */
	public function add_debug_information( array $information ): array {
		$information['akash_aagrawal_environment_guard'] = array(
			'label'       => __( 'Environment Guard', 'wp-environment-guard' ),
			'description' => __( 'Privacy-safe environment and policy status.', 'wp-environment-guard' ),
			'fields'      => array(
				'environment_type'   => array(
					'label' => __( 'WordPress environment type', 'wp-environment-guard' ),
					'value' => $this->environment->type(),
				),
				'environment_source' => array(
					'label' => __( 'Environment source', 'wp-environment-guard' ),
					'value' => $this->environment->source(),
				),
				'protection_status'  => array(
					'label' => __( 'Protection status', 'wp-environment-guard' ),
					'value' => $this->environment->is_guarded()
						? __( 'Active', 'wp-environment-guard' )
						: __( 'Inactive', 'wp-environment-guard' ),
				),
				'indexing_policy'    => array(
					'label' => __( 'Configured indexing policy', 'wp-environment-guard' ),
					'value' => $this->settings->protect_indexing()
						? __( 'Block', 'wp-environment-guard' )
						: __( 'Allow', 'wp-environment-guard' ),
				),
				'mail_policy'        => array(
					'label' => __( 'Configured mail policy', 'wp-environment-guard' ),
					'value' => $this->mail_mode_label(),
				),
				'http_policy'        => array(
					'label' => __( 'Configured HTTP policy', 'wp-environment-guard' ),
					'value' => $this->http_mode_label(),
				),
				'payment_policy'     => array(
					'label' => __( 'Configured payment policy', 'wp-environment-guard' ),
					'value' => $this->payment_mode_label(),
				),
				'webhook_policy'     => array(
					'label' => __( 'Configured webhook policy', 'wp-environment-guard' ),
					'value' => $this->settings->block_webhooks()
						? __( 'Block', 'wp-environment-guard' )
						: __( 'Allow', 'wp-environment-guard' ),
				),
			),
		);

		return $information;
	}

	/**
	 * Returns the translated configured mail mode.
	 */
	private function mail_mode_label(): string {
		return match ( $this->settings->mail_mode() ) {
			Settings::MAIL_MODE_BLOCK => __( 'Block', 'wp-environment-guard' ),
			Settings::MAIL_MODE_REDIRECT => __( 'Redirect', 'wp-environment-guard' ),
			default => __( 'Allow', 'wp-environment-guard' ),
		};
	}

	/**
	 * Returns the translated configured HTTP mode.
	 */
	private function http_mode_label(): string {
		return Settings::HTTP_MODE_ALLOWLIST === $this->settings->http_mode()
			? __( 'Exact-host allowlist', 'wp-environment-guard' )
			: __( 'Observe only', 'wp-environment-guard' );
	}

	/**
	 * Returns the translated configured payment mode.
	 */
	private function payment_mode_label(): string {
		return match ( $this->settings->payment_mode() ) {
			Settings::PAYMENT_MODE_BLOCK => __( 'Block all', 'wp-environment-guard' ),
			Settings::PAYMENT_MODE_ALLOWLIST => __( 'Allowlist', 'wp-environment-guard' ),
			default => __( 'Allow all', 'wp-environment-guard' ),
		};
	}
}
