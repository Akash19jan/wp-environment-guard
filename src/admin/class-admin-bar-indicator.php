<?php
/**
 * Adds the WordPress environment to the admin toolbar.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Admin;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use WP_Admin_Bar;

/**
 * Presents a text label for the active non-production environment.
 */
final readonly class Admin_Bar_Indicator implements Hookable {

	private const NODE_ID = 'akash-aagrawal-environment-guard';

	/**
	 * Creates the toolbar adapter.
	 *
	 * @param Environment_Context $environment Current request environment snapshot.
	 */
	public function __construct( private Environment_Context $environment ) {}

	/**
	 * Registers WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'admin_bar_menu', array( $this, 'add_indicator' ), 100 );
	}

	/**
	 * Adds a non-production status node to the toolbar.
	 *
	 * @param WP_Admin_Bar $admin_bar WordPress admin toolbar.
	 */
	public function add_indicator( WP_Admin_Bar $admin_bar ): void {
		if ( $this->environment->is_production() ) {
			return;
		}

		$environment_label = $this->environment_label( $this->environment->type() );
		$state_label       = $this->environment->is_guarded()
			? __( 'safeguards active', 'wp-environment-guard' )
			: __( 'safeguards disabled', 'wp-environment-guard' );

		/* translators: 1: Environment type. 2: Safeguard state. */
		$label = sprintf( __( 'Environment: %1$s — %2$s', 'wp-environment-guard' ), $environment_label, $state_label );
		$class = $this->environment->is_guarded()
			? 'akash-aagrawal-environment-guard-toolbar is-active'
			: 'akash-aagrawal-environment-guard-toolbar is-inactive';

		$node = array(
			'id'    => self::NODE_ID,
			'title' => sprintf(
				'<span class="akash-aagrawal-environment-guard-toolbar__label">%s</span>',
				esc_html( $label )
			),
			'meta'  => array(
				'class'      => $class,
				'aria-label' => $label,
			),
		);

		if ( current_user_can( 'manage_options' ) ) {
			$settings_url = admin_url( 'options-general.php?page=' . Settings_Page::PAGE_SLUG );

			if ( is_string( $settings_url ) ) {
				$node['href'] = $settings_url;
			}
		}

		$admin_bar->add_node( $node );
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
