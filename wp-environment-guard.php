<?php
/**
 * Plugin Name:       WP Environment Guard
 * Plugin URI:        https://github.com/Akash19jan/wp-environment-guard
 * Description:       Applies non-destructive safety policies to non-production WordPress environments.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Network:           false
 * Author:            Akash Agrawal
 * Author URI:        https://github.com/Akash19jan
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-environment-guard
 * Update URI:        https://github.com/Akash19jan/wp-environment-guard
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/src/lifecycle/class-dependency-guard.php';

$akash_aagrawal_environment_guard_dependency_guard = new \Akash_Aagrawal\Environment_Guard\Lifecycle\Dependency_Guard(
	__FILE__,
	__DIR__ . '/vendor/autoload.php'
);
$akash_aagrawal_environment_guard_dependency_guard->register_hooks();

if ( ! $akash_aagrawal_environment_guard_dependency_guard->is_ready() ) {

	return;
}

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook(
	__FILE__,
	array( \Akash_Aagrawal\Environment_Guard\Lifecycle\Activator::class, 'activate' )
);

\Akash_Aagrawal\Environment_Guard\Core\Bootstrap::boot( __FILE__ );
