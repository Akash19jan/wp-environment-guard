<?php
/**
 * WordPress hook function shims for isolated unit tests.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Support;

/**
 * Captures a WordPress action for assertions.
 *
 * @param string $hook_name Action name.
 * @param mixed  ...$args   Action arguments.
 */
function do_action( string $hook_name, mixed ...$args ): void {
	$action = array(
		'hook' => $hook_name,
		'args' => $args,
	);

	$GLOBALS['akash_aagrawal_environment_guard_test_action'] = $action;

	if ( ! isset( $GLOBALS['akash_aagrawal_environment_guard_test_actions'] )
		|| ! is_array( $GLOBALS['akash_aagrawal_environment_guard_test_actions'] )
	) {
		$GLOBALS['akash_aagrawal_environment_guard_test_actions'] = array();
	}

	$GLOBALS['akash_aagrawal_environment_guard_test_actions'][] = $action;
}
