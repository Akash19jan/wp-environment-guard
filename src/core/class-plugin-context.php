<?php
/**
 * Provides immutable plugin path and URL metadata.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Core;

/**
 * Immutable plugin metadata used by asset adapters.
 */
final readonly class Plugin_Context {

	/**
	 * Creates the plugin context.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 * @param string $version     Current plugin version.
	 */
	public function __construct(
		private string $plugin_file,
		private string $version
	) {}

	/**
	 * Returns an absolute path within the plugin.
	 *
	 * @param string $relative_path Optional relative path.
	 * @return string Absolute path.
	 */
	public function path( string $relative_path = '' ): string {
		return plugin_dir_path( $this->plugin_file ) . ltrim( $relative_path, '/' );
	}

	/**
	 * Returns a URL within the plugin.
	 *
	 * @param string $relative_path Optional relative path.
	 * @return string Plugin URL.
	 */
	public function url( string $relative_path = '' ): string {
		return plugin_dir_url( $this->plugin_file ) . ltrim( $relative_path, '/' );
	}

	/**
	 * Returns the plugin version.
	 */
	public function version(): string {
		return $this->version;
	}
}
