<?php
/**
 * Composes the plugin's runtime object graph.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Core;

use Akash_Aagrawal\Environment_Guard\Admin\Admin_Assets;
use Akash_Aagrawal\Environment_Guard\Admin\Admin_Bar_Indicator;
use Akash_Aagrawal\Environment_Guard\Admin\Admin_Notice;
use Akash_Aagrawal\Environment_Guard\Admin\Settings_Page;
use Akash_Aagrawal\Environment_Guard\CLI\CLI_Command;
use Akash_Aagrawal\Environment_Guard\CLI\CLI_Registrar;
use Akash_Aagrawal\Environment_Guard\Environment\WordPress_Environment_Detector;
use Akash_Aagrawal\Environment_Guard\Frontend\Environment_Banner;
use Akash_Aagrawal\Environment_Guard\Frontend\Frontend_Assets;
use Akash_Aagrawal\Environment_Guard\Integration\HTTP_Guard;
use Akash_Aagrawal\Environment_Guard\Integration\Indexing_Guard;
use Akash_Aagrawal\Environment_Guard\Integration\Mail_Guard;
use Akash_Aagrawal\Environment_Guard\Integration\Site_Health;
use Akash_Aagrawal\Environment_Guard\Integration\WooCommerce_Payment_Guard;
use Akash_Aagrawal\Environment_Guard\Integration\WooCommerce_Webhook_Guard;
use Akash_Aagrawal\Environment_Guard\Policy\Gateway_Policy;
use Akash_Aagrawal\Environment_Guard\Policy\HTTP_Policy;
use Akash_Aagrawal\Environment_Guard\Policy\Indexing_Policy;
use Akash_Aagrawal\Environment_Guard\Policy\Mail_Policy;
use Akash_Aagrawal\Environment_Guard\Policy\Safety_Audit;
use Akash_Aagrawal\Environment_Guard\Policy\Webhook_Policy;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use Akash_Aagrawal\Environment_Guard\Settings\Settings_Repository;
use Akash_Aagrawal\Environment_Guard\Settings\Settings_Sanitizer;
use Akash_Aagrawal\Environment_Guard\Support\Host_Matcher;
use Akash_Aagrawal\Environment_Guard\Support\Mail_Header_Sanitizer;
use Akash_Aagrawal\Environment_Guard\Support\Policy_Event_Emitter;

/**
 * Creates dependencies explicitly and starts the plugin.
 *
 * This manual composition root keeps construction visible, avoids global service
 * locators, and ensures environment detection occurs exactly once per request.
 */
final class Bootstrap {

	/**
	 * Boots the plugin from its main file.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 */
	public static function boot( string $plugin_file ): void {
		$environment    = ( new WordPress_Environment_Detector() )->detect();
		$repository     = new Settings_Repository();
		$settings       = $repository->get();
		$plugin_context = new Plugin_Context( $plugin_file, Plugin::VERSION );
		$event_emitter  = new Policy_Event_Emitter();

		$indexing_policy = new Indexing_Policy( $environment, $settings );
		$mail_policy     = new Mail_Policy( $environment, $settings );
		$http_policy     = new HTTP_Policy(
			$environment,
			$settings,
			new Host_Matcher( self::http_allowed_hosts( $settings ) )
		);
		$gateway_policy  = new Gateway_Policy( $environment, $settings );
		$webhook_policy  = new Webhook_Policy( $environment, $settings );
		$safety_audit    = new Safety_Audit( $environment, $settings, $mail_policy );

		$command = new CLI_Command(
			$environment,
			$indexing_policy,
			$mail_policy,
			$http_policy,
			$gateway_policy,
			$webhook_policy,
			$settings,
			$safety_audit
		);

		$plugin = new Plugin(
			array(
				new Indexing_Guard( $indexing_policy, $event_emitter ),
				new Mail_Guard( $mail_policy, $environment, new Mail_Header_Sanitizer(), $event_emitter ),
				new HTTP_Guard( $http_policy, $event_emitter ),
				new WooCommerce_Payment_Guard( $gateway_policy, $event_emitter ),
				new WooCommerce_Webhook_Guard( $webhook_policy, $event_emitter ),
				new Site_Health( $environment, $settings, $safety_audit ),
				new Settings_Page( $repository, new Settings_Sanitizer(), $environment ),
				new Admin_Assets( $plugin_context, $environment ),
				new Admin_Notice( $environment ),
				new Admin_Bar_Indicator( $environment ),
				new Environment_Banner( $repository, $environment ),
				new Frontend_Assets( $plugin_context, $repository, $environment ),
				new CLI_Registrar( $command ),
			)
		);

		$plugin->register_hooks();
	}

	/**
	 * Adds the current site's exact host to the configured HTTP allowlist.
	 *
	 * This permits loopback requests without allowing suffix or wildcard matches.
	 *
	 * @param Settings $settings Validated settings.
	 * @return list<string> Normalized exact hosts.
	 */
	private static function http_allowed_hosts( Settings $settings ): array {
		$allowed_hosts = $settings->http_allowed_hosts();
		$home_host     = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

		if ( is_string( $home_host ) && '' !== $home_host ) {
			$allowed_hosts[] = strtolower( rtrim( $home_host, '.' ) );
		}

		return array_values( array_unique( $allowed_hosts ) );
	}
}
