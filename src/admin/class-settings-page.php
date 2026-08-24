<?php
/**
 * Renders the Environment Guard settings screen.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Admin;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use Akash_Aagrawal\Environment_Guard\Settings\Settings_Provider;
use Akash_Aagrawal\Environment_Guard\Settings\Settings_Repository;
use Akash_Aagrawal\Environment_Guard\Settings\Settings_Sanitizer;

/**
 * Registers and renders the option-backed settings page.
 */
final class Settings_Page implements Hookable {

	public const PAGE_SLUG = 'wp-environment-guard';

	public const SETTINGS_GROUP = 'akash_aagrawal_environment_guard';

	/**
	 * Creates the settings page adapter.
	 *
	 * @param Settings_Provider   $settings    Validated settings provider.
	 * @param Settings_Sanitizer  $sanitizer   Settings input sanitizer.
	 * @param Environment_Context $environment Current request environment snapshot.
	 */
	public function __construct(
		private Settings_Provider $settings,
		private Settings_Sanitizer $sanitizer,
		private Environment_Context $environment
	) {}

	/**
	 * Registers WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
	}

	/**
	 * Registers the page under Settings.
	 */
	public function register_page(): void {
		add_options_page(
			esc_html__( 'Environment Guard', 'wp-environment-guard' ),
			esc_html__( 'Environment Guard', 'wp-environment-guard' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Registers the plugin option with the Settings API.
	 */
	public function register_setting(): void {
		register_setting(
			self::SETTINGS_GROUP,
			Settings_Repository::OPTION_NAME,
			array(
				'type'              => 'array',
				'default'           => Settings::defaults(),
				'sanitize_callback' => array( $this->sanitizer, 'sanitize' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Renders the settings page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to manage Environment Guard.', 'wp-environment-guard' ),
				esc_html__( 'Access denied', 'wp-environment-guard' ),
				array( 'response' => 403 )
			);
		}

		$settings = $this->settings->get();
		?>
		<div class="wrap akash-aagrawal-environment-guard-settings" data-environment-guard-settings>
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<p class="akash-aagrawal-environment-guard-settings__intro">
				<?php
				echo esc_html__(
					'Configure safeguards for non-production environments. This screen never changes the WordPress environment type.',
					'wp-environment-guard'
				);
				?>
			</p>

			<?php settings_errors( Settings_Repository::OPTION_NAME ); ?>
			<?php $this->render_environment_summary( $this->environment ); ?>

			<form action="options.php" method="post">
				<?php settings_fields( self::SETTINGS_GROUP ); ?>
				<?php $this->render_visibility_fields( $settings ); ?>
				<?php $this->render_mail_fields( $settings ); ?>
				<?php $this->render_http_fields( $settings ); ?>
				<?php $this->render_payment_fields( $settings ); ?>
				<?php $this->render_webhook_fields( $settings ); ?>
				<?php submit_button( esc_html__( 'Save safeguards', 'wp-environment-guard' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders a read-only summary of WordPress's environment selection.
	 *
	 * @param Environment_Context $environment Current environment context.
	 */
	private function render_environment_summary( Environment_Context $environment ): void {
		$state_class = $environment->is_guarded() ? 'is-active' : 'is-inactive';
		?>
		<section class="akash-aagrawal-environment-guard-settings__summary <?php echo esc_attr( $state_class ); ?>" aria-labelledby="akash-aagrawal-environment-guard-summary-title">
			<h2 id="akash-aagrawal-environment-guard-summary-title">
				<?php echo esc_html__( 'Current environment', 'wp-environment-guard' ); ?>
			</h2>
			<dl>
				<div>
					<dt><?php echo esc_html__( 'Type', 'wp-environment-guard' ); ?></dt>
					<dd><?php echo esc_html( $this->environment_type_label( $environment->type() ) ); ?></dd>
				</div>
				<div>
					<dt><?php echo esc_html__( 'Configured by', 'wp-environment-guard' ); ?></dt>
					<dd><?php echo esc_html( $this->environment_source_label( $environment->source() ) ); ?></dd>
				</div>
				<div>
					<dt><?php echo esc_html__( 'Safeguards', 'wp-environment-guard' ); ?></dt>
					<dd>
						<?php
						echo $environment->is_guarded()
							? esc_html__( 'Active', 'wp-environment-guard' )
							: esc_html__( 'Inactive', 'wp-environment-guard' );
						?>
					</dd>
				</div>
			</dl>

			<?php if ( $environment->is_production() ) : ?>
				<p>
					<?php echo esc_html__( 'Production is intentionally fail-open: Environment Guard does not alter site behavior.', 'wp-environment-guard' ); ?>
				</p>
			<?php elseif ( $environment->is_emergency_disabled() ) : ?>
				<p class="akash-aagrawal-environment-guard-settings__warning" role="alert">
					<strong><?php echo esc_html__( 'Warning:', 'wp-environment-guard' ); ?></strong>
					<?php echo esc_html__( 'the emergency disable constant is active, so configured safeguards are not running.', 'wp-environment-guard' ); ?>
				</p>
			<?php else : ?>
				<p><?php echo esc_html__( 'Configured safeguards are eligible to run on this non-production environment.', 'wp-environment-guard' ); ?></p>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Renders visibility safeguards.
	 *
	 * @param Settings $settings Current validated settings.
	 */
	private function render_visibility_fields( Settings $settings ): void {
		?>
		<fieldset class="akash-aagrawal-environment-guard-settings__group">
			<legend><?php echo esc_html__( 'Visibility', 'wp-environment-guard' ); ?></legend>
			<p class="description" id="akash-aagrawal-environment-guard-visibility-description">
				<?php echo esc_html__( 'These controls make a guarded environment obvious to people and search engines.', 'wp-environment-guard' ); ?>
			</p>

			<?php
			$this->render_checkbox(
				'show_frontend_banner',
				$settings->show_frontend_banner(),
				esc_html__( 'Show a non-production banner on public pages', 'wp-environment-guard' ),
				esc_html__( 'The banner includes an environment label in text, so it does not rely on color alone.', 'wp-environment-guard' )
			);
			$this->render_checkbox(
				'protect_indexing',
				$settings->protect_indexing(),
				esc_html__( 'Discourage search indexing and disable WordPress sitemaps', 'wp-environment-guard' ),
				esc_html__( 'This is a defense-in-depth signal, not an access-control boundary.', 'wp-environment-guard' )
			);
			?>
		</fieldset>
		<?php
	}

	/**
	 * Renders outgoing mail controls.
	 *
	 * @param Settings $settings Current validated settings.
	 */
	private function render_mail_fields( Settings $settings ): void {
		?>
		<fieldset class="akash-aagrawal-environment-guard-settings__group">
			<legend><?php echo esc_html__( 'Outgoing email', 'wp-environment-guard' ); ?></legend>
			<p class="description" id="akash-aagrawal-environment-guard-mail-description">
				<?php echo esc_html__( 'Choose how WordPress mail is handled while safeguards are active.', 'wp-environment-guard' ); ?>
			</p>
			<div class="akash-aagrawal-environment-guard-settings__choices">
				<?php
				$this->render_radio(
					'mail_mode',
					Settings::MAIL_MODE_BLOCK,
					$settings->mail_mode(),
					esc_html__( 'Block email (recommended)', 'wp-environment-guard' ),
					esc_html__( 'Report success to the caller without handing the message to the mail transport.', 'wp-environment-guard' ),
					'mail-redirect'
				);
				$this->render_radio(
					'mail_mode',
					Settings::MAIL_MODE_REDIRECT,
					$settings->mail_mode(),
					esc_html__( 'Redirect every message', 'wp-environment-guard' ),
					esc_html__( 'Replace recipients with one safe address and remove recipient-bearing headers.', 'wp-environment-guard' ),
					'mail-redirect'
				);
				$this->render_radio(
					'mail_mode',
					Settings::MAIL_MODE_ALLOW,
					$settings->mail_mode(),
					esc_html__( 'Allow email unchanged', 'wp-environment-guard' ),
					esc_html__( 'Do not apply an outgoing email safeguard.', 'wp-environment-guard' ),
					'mail-redirect'
				);
				?>
			</div>

			<div class="akash-aagrawal-environment-guard-settings__conditional" data-environment-guard-conditional="mail-redirect" data-environment-guard-show-when="redirect">
				<label for="akash-aagrawal-environment-guard-mail-redirect-address">
					<?php echo esc_html__( 'Safe redirect address', 'wp-environment-guard' ); ?>
				</label>
				<input
					class="regular-text"
					id="akash-aagrawal-environment-guard-mail-redirect-address"
					name="<?php echo esc_attr( Settings_Repository::OPTION_NAME ); ?>[mail_redirect_address]"
					type="email"
					value="<?php echo esc_attr( $settings->mail_redirect_address() ); ?>"
					aria-describedby="akash-aagrawal-environment-guard-mail-redirect-description"
					data-environment-guard-required
				/>
				<p class="description" id="akash-aagrawal-environment-guard-mail-redirect-description">
					<?php echo esc_html__( 'Required in Redirect mode. Invalid or empty values fail safely to Block mode.', 'wp-environment-guard' ); ?>
				</p>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Renders outbound HTTP controls.
	 *
	 * @param Settings $settings Current validated settings.
	 */
	private function render_http_fields( Settings $settings ): void {
		?>
		<fieldset class="akash-aagrawal-environment-guard-settings__group">
			<legend><?php echo esc_html__( 'Outbound HTTP requests', 'wp-environment-guard' ); ?></legend>
			<p class="description" id="akash-aagrawal-environment-guard-http-description">
				<?php echo esc_html__( 'Observe requests or restrict them to an exact hostname allowlist.', 'wp-environment-guard' ); ?>
			</p>
			<div class="akash-aagrawal-environment-guard-settings__choices">
				<?php
				$this->render_radio(
					'http_mode',
					Settings::HTTP_MODE_OBSERVE,
					$settings->http_mode(),
					esc_html__( 'Observe only (recommended initially)', 'wp-environment-guard' ),
					esc_html__( 'Allow requests while exposing privacy-safe policy decisions to developer hooks.', 'wp-environment-guard' ),
					'http-allowlist'
				);
				$this->render_radio(
					'http_mode',
					Settings::HTTP_MODE_ALLOWLIST,
					$settings->http_mode(),
					esc_html__( 'Enforce exact-host allowlist', 'wp-environment-guard' ),
					esc_html__( 'Block unmatched hosts and disable redirects to prevent redirect escapes.', 'wp-environment-guard' ),
					'http-allowlist'
				);
				?>
			</div>

			<div class="akash-aagrawal-environment-guard-settings__conditional" data-environment-guard-conditional="http-allowlist" data-environment-guard-show-when="allowlist">
				<label for="akash-aagrawal-environment-guard-http-allowed-hosts">
					<?php echo esc_html__( 'Allowed HTTP hosts', 'wp-environment-guard' ); ?>
				</label>
				<textarea
					class="large-text code"
					id="akash-aagrawal-environment-guard-http-allowed-hosts"
					name="<?php echo esc_attr( Settings_Repository::OPTION_NAME ); ?>[http_allowed_hosts]"
					rows="6"
					aria-describedby="akash-aagrawal-environment-guard-http-allowed-hosts-description"
				><?php echo esc_textarea( implode( "\n", $settings->http_allowed_hosts() ) ); ?></textarea>
				<p class="description" id="akash-aagrawal-environment-guard-http-allowed-hosts-description">
					<?php echo esc_html__( 'Enter one hostname per line. URLs and wildcard suffixes are rejected.', 'wp-environment-guard' ); ?>
				</p>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Renders WooCommerce payment controls.
	 *
	 * @param Settings $settings Current validated settings.
	 */
	private function render_payment_fields( Settings $settings ): void {
		?>
		<fieldset class="akash-aagrawal-environment-guard-settings__group">
			<legend><?php echo esc_html__( 'WooCommerce payments', 'wp-environment-guard' ); ?></legend>
			<p class="description" id="akash-aagrawal-environment-guard-payment-description">
				<?php echo esc_html__( 'Control which gateways customers can select. Gateway settings are never rewritten.', 'wp-environment-guard' ); ?>
			</p>
			<div class="akash-aagrawal-environment-guard-settings__choices">
				<?php
				$this->render_radio(
					'payment_mode',
					Settings::PAYMENT_MODE_ALLOWLIST,
					$settings->payment_mode(),
					esc_html__( 'Allow listed gateways (recommended)', 'wp-environment-guard' ),
					esc_html__( 'Remove every available gateway whose ID is not listed below.', 'wp-environment-guard' ),
					'payment-allowlist'
				);
				$this->render_radio(
					'payment_mode',
					Settings::PAYMENT_MODE_BLOCK,
					$settings->payment_mode(),
					esc_html__( 'Block all gateways', 'wp-environment-guard' ),
					esc_html__( 'Remove every payment gateway from checkout.', 'wp-environment-guard' ),
					'payment-allowlist'
				);
				$this->render_radio(
					'payment_mode',
					Settings::PAYMENT_MODE_ALLOW,
					$settings->payment_mode(),
					esc_html__( 'Allow gateways unchanged', 'wp-environment-guard' ),
					esc_html__( 'Do not apply a WooCommerce payment safeguard.', 'wp-environment-guard' ),
					'payment-allowlist'
				);
				?>
			</div>

			<div class="akash-aagrawal-environment-guard-settings__conditional" data-environment-guard-conditional="payment-allowlist" data-environment-guard-show-when="allowlist">
				<label for="akash-aagrawal-environment-guard-payment-allowed-gateways">
					<?php echo esc_html__( 'Allowed gateway IDs', 'wp-environment-guard' ); ?>
				</label>
				<textarea
					class="large-text code"
					id="akash-aagrawal-environment-guard-payment-allowed-gateways"
					name="<?php echo esc_attr( Settings_Repository::OPTION_NAME ); ?>[payment_allowed_gateways]"
					rows="5"
					aria-describedby="akash-aagrawal-environment-guard-payment-allowed-gateways-description"
				><?php echo esc_textarea( implode( "\n", $settings->payment_allowed_gateways() ) ); ?></textarea>
				<p class="description" id="akash-aagrawal-environment-guard-payment-allowed-gateways-description">
					<?php echo esc_html__( 'Enter one WooCommerce gateway ID per line, such as bacs, cheque, or cod.', 'wp-environment-guard' ); ?>
				</p>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Renders WooCommerce webhook controls.
	 *
	 * @param Settings $settings Current validated settings.
	 */
	private function render_webhook_fields( Settings $settings ): void {
		?>
		<fieldset class="akash-aagrawal-environment-guard-settings__group">
			<legend><?php echo esc_html__( 'WooCommerce webhooks', 'wp-environment-guard' ); ?></legend>
			<?php
			$this->render_checkbox(
				'block_webhooks',
				$settings->block_webhooks(),
				esc_html__( 'Block webhook delivery', 'wp-environment-guard' ),
				esc_html__( 'Prevent WooCommerce from delivering webhook payloads while safeguards are active.', 'wp-environment-guard' )
			);
			?>
		</fieldset>
		<?php
	}

	/**
	 * Renders one accessible checkbox control.
	 *
	 * @param string $field       Option field name.
	 * @param bool   $is_checked  Whether the checkbox is checked.
	 * @param string $label       Visible checkbox label.
	 * @param string $description Supporting description.
	 */
	private function render_checkbox( string $field, bool $is_checked, string $label, string $description ): void {
		$field_id       = 'akash-aagrawal-environment-guard-' . str_replace( '_', '-', $field );
		$description_id = $field_id . '-description';
		?>
		<div class="akash-aagrawal-environment-guard-settings__control">
			<input
				id="<?php echo esc_attr( $field_id ); ?>"
				name="<?php echo esc_attr( Settings_Repository::OPTION_NAME ); ?>[<?php echo esc_attr( $field ); ?>]"
				type="checkbox"
				value="1"
				aria-describedby="<?php echo esc_attr( $description_id ); ?>"
				<?php checked( $is_checked ); ?>
			/>
			<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			<p class="description" id="<?php echo esc_attr( $description_id ); ?>"><?php echo esc_html( $description ); ?></p>
		</div>
		<?php
	}

	/**
	 * Renders one accessible radio option.
	 *
	 * @param string $field              Option field name.
	 * @param string $value              Radio option value.
	 * @param string $current            Current option value.
	 * @param string $label              Visible radio label.
	 * @param string $description        Supporting description.
	 * @param string $conditional_target Conditional region controlled by the group.
	 */
	private function render_radio(
		string $field,
		string $value,
		string $current,
		string $label,
		string $description,
		string $conditional_target
	): void {
		$field_id             = sprintf( 'akash-aagrawal-environment-guard-%s-%s', str_replace( '_', '-', $field ), $value );
		$description_id       = $field_id . '-description';
		$group_description_id = sprintf(
			'akash-aagrawal-environment-guard-%s-description',
			str_replace( '_mode', '', $field )
		);
		?>
		<div class="akash-aagrawal-environment-guard-settings__choice">
			<input
				id="<?php echo esc_attr( $field_id ); ?>"
				name="<?php echo esc_attr( Settings_Repository::OPTION_NAME ); ?>[<?php echo esc_attr( $field ); ?>]"
				type="radio"
				value="<?php echo esc_attr( $value ); ?>"
				aria-describedby="<?php echo esc_attr( $group_description_id . ' ' . $description_id ); ?>"
				data-environment-guard-controls="<?php echo esc_attr( $conditional_target ); ?>"
				<?php checked( $current, $value ); ?>
			/>
			<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			<p class="description" id="<?php echo esc_attr( $description_id ); ?>"><?php echo esc_html( $description ); ?></p>
		</div>
		<?php
	}

	/**
	 * Returns a translated environment type label.
	 *
	 * @param string $type Normalized environment type.
	 */
	private function environment_type_label( string $type ): string {
		$labels = array(
			Environment_Context::TYPE_LOCAL       => __( 'Local', 'wp-environment-guard' ),
			Environment_Context::TYPE_DEVELOPMENT => __( 'Development', 'wp-environment-guard' ),
			Environment_Context::TYPE_STAGING     => __( 'Staging', 'wp-environment-guard' ),
			Environment_Context::TYPE_PRODUCTION  => __( 'Production', 'wp-environment-guard' ),
		);

		return $labels[ $type ] ?? __( 'Production', 'wp-environment-guard' );
	}

	/**
	 * Returns a translated environment source label.
	 *
	 * @param string $source Environment source identifier.
	 */
	private function environment_source_label( string $source ): string {
		$labels = array(
			Environment_Context::SOURCE_CONSTANT    => __( 'WP_ENVIRONMENT_TYPE constant', 'wp-environment-guard' ),
			Environment_Context::SOURCE_ENVIRONMENT => __( 'WP_ENVIRONMENT_TYPE environment variable', 'wp-environment-guard' ),
			Environment_Context::SOURCE_DEFAULT     => __( 'WordPress default', 'wp-environment-guard' ),
		);

		return $labels[ $source ] ?? __( 'WordPress default', 'wp-environment-guard' );
	}
}
