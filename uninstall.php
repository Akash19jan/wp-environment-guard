<?php
/**
 * Removes data created by WP Environment Guard.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	require_once __DIR__ . '/src/lifecycle/class-uninstaller.php';
}

\Akash_Aagrawal\Environment_Guard\Lifecycle\Uninstaller::uninstall();
