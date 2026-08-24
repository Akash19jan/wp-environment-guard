<?php
/**
 * Bootstraps isolated unit tests.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

$akash_aagrawal_environment_guard_autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! is_readable( $akash_aagrawal_environment_guard_autoloader ) ) {
	throw new RuntimeException( 'Composer dependencies are required to run the unit tests.' );
}

require $akash_aagrawal_environment_guard_autoloader;
require __DIR__ . '/stubs/class-wp-error.php';
require __DIR__ . '/stubs/wordpress-hook-functions.php';
require __DIR__ . '/stubs/wordpress-integration-functions.php';
require __DIR__ . '/stubs/wordpress-lifecycle-functions.php';
require __DIR__ . '/stubs/wordpress-url-functions.php';
