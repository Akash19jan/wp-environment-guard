<?php
/**
 * WordPress lifecycle function shims for isolated unit tests.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Lifecycle;

use RuntimeException;

/**
 * Captures an activation-hook registration.
 *
 * @param string   $plugin_file Absolute plugin file path.
 * @param callable $callback    Activation callback.
 */
function register_activation_hook( string $plugin_file, callable $callback ): void {
	$GLOBALS['akash_aagrawal_environment_guard_test_activation_hook'] = array(
		'plugin_file' => $plugin_file,
		'callback'    => $callback,
	);
}

/**
 * Captures an action registration.
 *
 * @param string   $hook_name Action name.
 * @param callable $callback  Action callback.
 */
function add_action( string $hook_name, callable $callback ): void {
	if ( ! isset( $GLOBALS['akash_aagrawal_environment_guard_test_registered_actions'] )
		|| ! is_array( $GLOBALS['akash_aagrawal_environment_guard_test_registered_actions'] )
	) {
		$GLOBALS['akash_aagrawal_environment_guard_test_registered_actions'] = array();
	}

	$GLOBALS['akash_aagrawal_environment_guard_test_registered_actions'][ $hook_name ] = $callback;
}

/**
 * Returns untranslated test text.
 *
 * @param string $text   Source text.
 * @param string $domain Translation domain.
 */
function esc_html__( string $text, string $domain = 'default' ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Mirrors the WordPress translation signature.
	return $text;
}

/**
 * Converts a WordPress fatal response into a test exception.
 *
 * @param string               $message Error message.
 * @param string               $title   Error title.
 * @param array<string, mixed> $args    Error response arguments.
 * @throws RuntimeException Always, matching wp_die() termination.
 */
function wp_die( string $message, string $title = '', array $args = array() ): never { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Mirrors WordPress termination.
	throw new RuntimeException( $message );
}

/**
 * Returns the configured Multisite state.
 */
function is_multisite(): bool {
	return true === ( $GLOBALS['akash_aagrawal_environment_guard_test_is_multisite'] ?? false );
}

/**
 * Captures an option installation.
 *
 * @param string $option     Option name.
 * @param mixed  $value      Option value.
 * @param string $deprecated Deprecated parameter.
 * @param bool   $autoload   Whether WordPress should autoload the option.
 */
function add_option( string $option, mixed $value, string $deprecated = '', bool $autoload = true ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Mirrors the WordPress option signature.
	$GLOBALS['akash_aagrawal_environment_guard_test_added_option'] = array(
		'name'     => $option,
		'value'    => $value,
		'autoload' => $autoload,
	);

	return true;
}

/**
 * Captures an option deletion.
 *
 * @param string $option Option name.
 */
function delete_option( string $option ): bool {
	$GLOBALS['akash_aagrawal_environment_guard_test_deleted_option'] = $option;

	return true;
}

/**
 * Returns a stable plugin basename for test paths.
 *
 * @param string $plugin_file Absolute plugin file path.
 */
function plugin_basename( string $plugin_file ): string {
	return basename( dirname( $plugin_file ) ) . '/' . basename( $plugin_file );
}

/**
 * Captures self-deactivation.
 *
 * @param string $plugin       Plugin basename.
 * @param bool   $silent       Whether deactivation hooks are suppressed.
 * @param bool   $network_wide Whether this is a network deactivation.
 */
function deactivate_plugins( string $plugin, bool $silent = false, bool $network_wide = false ): void {
	$GLOBALS['akash_aagrawal_environment_guard_test_deactivated_plugin'] = array(
		'plugin'       => $plugin,
		'silent'       => $silent,
		'network_wide' => $network_wide,
	);
}
