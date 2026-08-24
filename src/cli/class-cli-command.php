<?php
/**
 * Implements the WP-CLI user interface.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\CLI;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Policy\Gateway_Policy;
use Akash_Aagrawal\Environment_Guard\Policy\HTTP_Policy;
use Akash_Aagrawal\Environment_Guard\Policy\Indexing_Policy;
use Akash_Aagrawal\Environment_Guard\Policy\Mail_Policy;
use Akash_Aagrawal\Environment_Guard\Policy\Safety_Audit;
use Akash_Aagrawal\Environment_Guard\Policy\Webhook_Policy;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use WP_CLI;

/**
 * Reports privacy-safe policy state to WP-CLI.
 */
final readonly class CLI_Command {

	/**
	 * Creates the CLI command.
	 *
	 * @param Environment_Context $environment     Current environment.
	 * @param Indexing_Policy     $indexing_policy Indexing policy.
	 * @param Mail_Policy         $mail_policy     Mail policy.
	 * @param HTTP_Policy         $http_policy     HTTP policy.
	 * @param Gateway_Policy      $gateway_policy  Payment gateway policy.
	 * @param Webhook_Policy      $webhook_policy  Webhook policy.
	 * @param Settings            $settings        Validated settings.
	 * @param Safety_Audit        $safety_audit    Deployment safety audit.
	 */
	public function __construct(
		private Environment_Context $environment,
		private Indexing_Policy $indexing_policy,
		private Mail_Policy $mail_policy,
		private HTTP_Policy $http_policy,
		private Gateway_Policy $gateway_policy,
		private Webhook_Policy $webhook_policy,
		private Settings $settings,
		private Safety_Audit $safety_audit
	) {}

	/**
	 * Displays the active environment and policy decisions.
	 *
	 * ## EXAMPLES
	 *
	 *     wp environment-guard status
	 */
	public function status(): void {
		foreach ( $this->status_lines() as $line ) {
			WP_CLI::log( $line );
		}
	}

	/**
	 * Checks environment guard invariants for deployment automation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp environment-guard check
	 */
	public function check(): void {
		$failures = $this->safety_audit->failures();

		if ( array() !== $failures ) {
			WP_CLI::error( 'Environment Guard check failed: ' . implode( ', ', $failures ) . '.' );
		}

		WP_CLI::success( 'WP Environment Guard invariants passed.' );
	}

	/**
	 * Builds sanitized status lines.
	 *
	 * @return list<string> Privacy-safe status lines.
	 */
	private function status_lines(): array {
		return array(
			'Environment: ' . $this->environment->type(),
			'Environment source: ' . $this->environment->source(),
			'Guard active: ' . ( $this->environment->is_guarded() ? 'yes' : 'no' ),
			'Indexing: ' . $this->indexing_policy->decide()->action(),
			'Mail: ' . $this->mail_policy->decide()->action(),
			'HTTP mode: ' . $this->settings->http_mode(),
			'HTTP redirects prevented: ' . ( $this->http_policy->prevents_redirects() ? 'yes' : 'no' ),
			'Unknown payment gateways: ' . $this->gateway_policy->decide( 'unknown' )->action(),
			'Webhooks: ' . $this->webhook_policy->decide()->action(),
		);
	}
}
