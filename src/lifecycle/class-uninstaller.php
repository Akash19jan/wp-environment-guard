<?php
/**
 * Handles plugin uninstall cleanup.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Lifecycle;

/**
 * Deletes data owned exclusively by this plugin.
 */
final class Uninstaller {

	private const OPTION_NAME = 'akash_aagrawal_environment_guard_settings';

	/**
	 * Removes the plugin settings option.
	 */
	public static function uninstall(): void {
		delete_option( self::OPTION_NAME );
	}
}
