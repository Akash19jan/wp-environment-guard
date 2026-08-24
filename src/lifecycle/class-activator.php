<?php
/**
 * Handles plugin activation.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Lifecycle;

use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use Akash_Aagrawal\Environment_Guard\Settings\Settings_Repository;

/**
 * Installs only the plugin's own default option.
 */
final class Activator {

	/**
	 * Adds defaults without overwriting existing configuration.
	 *
	 * @param bool $network_wide Whether WordPress requested a network-wide activation.
	 */
	public static function activate( bool $network_wide = false ): void {
		if ( is_multisite() || $network_wide ) {
			wp_die(
				esc_html__( 'WP Environment Guard 0.1 supports single-site WordPress installations only.', 'wp-environment-guard' ),
				esc_html__( 'Plugin activation blocked', 'wp-environment-guard' ),
				array( 'back_link' => true )
			);
		}

		add_option(
			Settings_Repository::OPTION_NAME,
			Settings::defaults(),
			'',
			false
		);
	}
}
