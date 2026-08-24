<?php
/**
 * Displays a non-production environment notice in wp-admin.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Admin;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;

/**
 * Adds a text-first notice when WordPress is not in production.
 */
final readonly class Admin_Notice implements Hookable {

	/**
	 * Creates the admin notice adapter.
	 *
	 * @param Environment_Context $environment Current request environment snapshot.
	 */
	public function __construct( private Environment_Context $environment ) {}

	/**
	 * Registers WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'admin_notices', array( $this, 'render' ) );
	}

	/**
	 * Renders the notice for non-production environments.
	 */
	public function render(): void {
		if ( $this->environment->is_production() ) {
			return;
		}

		$notice_class = $this->environment->is_guarded() ? 'notice-warning' : 'notice-error';
		$state        = $this->environment->is_guarded()
			? __( 'Safeguards are active.', 'wp-environment-guard' )
			: __( 'Safeguards are disabled by the emergency constant.', 'wp-environment-guard' );
		?>
		<div class="notice <?php echo esc_attr( $notice_class ); ?> akash-aagrawal-environment-guard-notice" role="<?php echo esc_attr( $this->environment->is_guarded() ? 'status' : 'alert' ); ?>">
			<p>
				<strong>
					<?php
					/* translators: %s: Human-readable WordPress environment type. */
					echo esc_html( sprintf( __( '%s environment', 'wp-environment-guard' ), $this->environment_label( $this->environment->type() ) ) );
					?>
				</strong>
				<?php echo esc_html( $state ); ?>
			</p>
		</div>
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
