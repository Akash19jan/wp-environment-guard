<?php
/**
 * Registers WP Environment Guard with WP-CLI.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\CLI;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use WP_CLI;

/**
 * Defers command registration until WP-CLI is initialized.
 */
final readonly class CLI_Registrar implements Hookable {

	/**
	 * Creates the registrar.
	 *
	 * @param CLI_Command $command Command implementation.
	 */
	public function __construct( private CLI_Command $command ) {}

	/**
	 * Registers CLI initialization when WP-CLI is available.
	 */
	public function register_hooks(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		add_action( 'cli_init', array( $this, 'register_command' ) );
	}

	/**
	 * Registers the root command.
	 */
	public function register_command(): void {
		WP_CLI::add_command( 'environment-guard', $this->command );
	}
}
