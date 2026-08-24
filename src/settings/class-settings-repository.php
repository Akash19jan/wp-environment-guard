<?php
/**
 * Reads environment guard settings from WordPress.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Settings;

/**
 * Option-backed settings provider with request-local caching.
 */
final class Settings_Repository implements Settings_Provider {

	public const OPTION_NAME = 'akash_aagrawal_environment_guard_settings';

	/**
	 * Request-local settings snapshot.
	 *
	 * @var Settings|null
	 */
	private ?Settings $settings = null;

	/**
	 * Returns validated settings.
	 */
	public function get(): Settings {
		if ( $this->settings instanceof Settings ) {
			return $this->settings;
		}

		$stored = get_option( self::OPTION_NAME, array() );
		$stored = is_array( $stored ) ? $stored : array();

		$this->settings = Settings::from_array( $stored );

		return $this->settings;
	}
}
