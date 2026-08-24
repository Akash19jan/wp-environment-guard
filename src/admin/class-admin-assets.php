<?php
/**
 * Loads admin presentation assets where they are needed.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Admin;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use Akash_Aagrawal\Environment_Guard\Core\Plugin_Context;
use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;

/**
 * Enqueues admin assets only for the settings screen or toolbar indicator.
 */
final readonly class Admin_Assets implements Hookable {

	private const STYLE_HANDLE = 'akash-aagrawal-environment-guard-admin';

	private const SCRIPT_HANDLE = 'akash-aagrawal-environment-guard-admin';

	/**
	 * Creates the admin asset adapter.
	 *
	 * @param Plugin_Context      $plugin_context Plugin path and URL metadata.
	 * @param Environment_Context $environment    Current request environment snapshot.
	 */
	public function __construct(
		private Plugin_Context $plugin_context,
		private Environment_Context $environment
	) {}

	/**
	 * Registers WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueues assets for relevant admin screens.
	 *
	 * @param string $hook_suffix Current admin page suffix.
	 */
	public function enqueue( string $hook_suffix ): void {
		$is_settings_page = 'settings_page_' . Settings_Page::PAGE_SLUG === $hook_suffix;

		if ( ! $is_settings_page && $this->environment->is_production() ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			$this->plugin_context->url( 'assets/build/admin.css' ),
			array(),
			$this->plugin_context->version()
		);
		wp_style_add_data( self::STYLE_HANDLE, 'rtl', 'replace' );

		if ( ! $is_settings_page ) {
			return;
		}

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			$this->plugin_context->url( 'assets/build/admin.js' ),
			array(),
			$this->plugin_context->version(),
			true
		);
	}
}
