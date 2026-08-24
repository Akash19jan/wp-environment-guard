<?php
/**
 * Displays a public non-production environment banner.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Frontend;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Settings\Settings_Provider;

/**
 * Renders an accessible banner at wp_body_open with a footer fallback.
 */
final class Environment_Banner implements Hookable {

	/**
	 * Whether the banner has already been rendered during this request.
	 *
	 * @var bool
	 */
	private bool $has_rendered = false;

	/**
	 * Creates the frontend banner adapter.
	 *
	 * @param Settings_Provider   $settings    Validated settings provider.
	 * @param Environment_Context $environment Current request environment snapshot.
	 */
	public function __construct(
		private Settings_Provider $settings,
		private Environment_Context $environment
	) {}

	/**
	 * Registers WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'wp_body_open', array( $this, 'render' ), 1 );
		add_action( 'wp_footer', array( $this, 'render' ), 1 );
	}

	/**
	 * Renders the banner once per page.
	 */
	public function render(): void {
		if ( $this->has_rendered || ! $this->settings->get()->show_frontend_banner() ) {
			return;
		}

		if ( $this->environment->is_production() ) {
			return;
		}

		$this->has_rendered = true;
		$state_class        = $this->environment->is_guarded() ? 'is-active' : 'is-inactive';
		$environment_label  = $this->environment_label( $this->environment->type() );
		$message            = $this->environment->is_guarded()
			? __( 'Safeguards may restrict email, external requests, payments, and webhooks.', 'wp-environment-guard' )
			: __( 'Warning: the emergency disable constant is active and safeguards are not running.', 'wp-environment-guard' );
		?>
		<aside class="akash-aagrawal-environment-guard-banner <?php echo esc_attr( $state_class ); ?>" aria-label="<?php echo esc_attr__( 'Non-production environment', 'wp-environment-guard' ); ?>">
			<div class="akash-aagrawal-environment-guard-banner__inner">
				<strong class="akash-aagrawal-environment-guard-banner__title">
					<?php
					/* translators: %s: Human-readable WordPress environment type. */
					echo esc_html( sprintf( __( '%s environment', 'wp-environment-guard' ), $environment_label ) );
					?>
				</strong>
				<span class="akash-aagrawal-environment-guard-banner__message"><?php echo esc_html( $message ); ?></span>
			</div>
		</aside>
		<?php
	}

	/**
	 * Returns a translated environment label.
	 *
	 * @param string $type Normalized environment type.
	 */
	private function environment_label( string $type ): string {
		$labels = array(
			Environment_Context::TYPE_LOCAL       => __( 'Local', 'wp-environment-guard' ),
			Environment_Context::TYPE_DEVELOPMENT => __( 'Development', 'wp-environment-guard' ),
			Environment_Context::TYPE_STAGING     => __( 'Staging', 'wp-environment-guard' ),
		);

		return $labels[ $type ] ?? __( 'Non-production', 'wp-environment-guard' );
	}
}
