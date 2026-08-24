<?php
/**
 * Guards startup when packaged Composer dependencies are unavailable.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Lifecycle;

/**
 * Fails activation visibly and self-deactivates an incomplete installation.
 *
 * This class is loaded directly by the plugin entry point because the Composer
 * autoloader is the dependency it verifies.
 */
final readonly class Dependency_Guard {

	/**
	 * Creates the dependency guard.
	 *
	 * @param string $plugin_file  Absolute path to the main plugin file.
	 * @param string $autoload_file Absolute path to Composer's autoloader.
	 */
	public function __construct(
		private string $plugin_file,
		private string $autoload_file
	) {}

	/**
	 * Registers lifecycle and recovery hooks.
	 */
	public function register_hooks(): void {
		register_activation_hook( $this->plugin_file, array( $this, 'assert_ready_for_activation' ) );

		if ( $this->is_ready() ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'deactivate_incomplete_installation' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notice' ) );
	}

	/**
	 * Returns whether the packaged autoloader can be read.
	 */
	public function is_ready(): bool {
		return is_readable( $this->autoload_file );
	}

	/**
	 * Stops WordPress from marking an incomplete copy active.
	 */
	public function assert_ready_for_activation(): void {
		if ( $this->is_ready() ) {
			return;
		}

		wp_die(
			esc_html__( 'WP Environment Guard cannot be activated because its Composer autoloader is missing. Install a packaged release, or run Composer for a source checkout.', 'wp-environment-guard' ),
			esc_html__( 'Plugin activation blocked', 'wp-environment-guard' ),
			array( 'back_link' => true )
		);
	}

	/**
	 * Deactivates a previously active copy after an incomplete update.
	 */
	public function deactivate_incomplete_installation(): void {
		if ( $this->is_ready() ) {
			return;
		}

		deactivate_plugins( plugin_basename( $this->plugin_file ), true );
	}

	/**
	 * Explains how to recover from an incomplete installation.
	 */
	public function render_admin_notice(): void {
		if ( $this->is_ready() ) {
			return;
		}
		?>
		<div class="notice notice-error" role="alert">
			<p>
				<?php
				echo esc_html__( 'WP Environment Guard was deactivated because its Composer autoloader is missing. Install a packaged release, or run Composer for a source checkout.', 'wp-environment-guard' );
				?>
			</p>
		</div>
		<?php
	}
}
