<?php
/**
 * Loads public presentation assets where they are needed.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Frontend;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use Akash_Aagrawal\Environment_Guard\Core\Plugin_Context;
use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Settings\Settings_Provider;

/**
 * Enqueues the compiled frontend stylesheet for visible plugin UI.
 */
final readonly class Frontend_Assets implements Hookable {

	private const STYLE_HANDLE = 'akash-aagrawal-environment-guard-frontend';

	/**
	 * Creates the frontend asset adapter.
	 *
	 * @param Plugin_Context      $plugin_context Plugin path and URL metadata.
	 * @param Settings_Provider   $settings       Validated settings provider.
	 * @param Environment_Context $environment    Current request environment snapshot.
	 */
	public function __construct(
		private Plugin_Context $plugin_context,
		private Settings_Provider $settings,
		private Environment_Context $environment
	) {}

	/**
	 * Registers WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueues styles only when non-production UI can be visible.
	 */
	public function enqueue(): void {
		$shows_banner = $this->settings->get()->show_frontend_banner();

		if ( $this->environment->is_production() || ( ! $shows_banner && ! is_admin_bar_showing() ) ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			$this->plugin_context->url( 'assets/build/frontend.css' ),
			array(),
			$this->plugin_context->version()
		);
		wp_style_add_data( self::STYLE_HANDLE, 'rtl', 'replace' );
	}
}
