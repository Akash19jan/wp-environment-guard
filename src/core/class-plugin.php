<?php
/**
 * Coordinates the plugin's hook adapters.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Core;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;

/**
 * Registers each composed plugin service.
 */
final class Plugin {

	/**
	 * Current plugin version.
	 */
	public const VERSION = '0.1.0';

	/**
	 * Hook-aware services.
	 *
	 * @var list<Hookable>
	 */
	private array $services;

	/**
	 * Creates the plugin coordinator.
	 *
	 * @param array $services Hook-aware services.
	 * @phpstan-param list<Hookable> $services Hook-aware services.
	 */
	public function __construct( array $services ) {
		$this->services = $services;
	}

	/**
	 * Registers all services with WordPress.
	 */
	public function register_hooks(): void {
		foreach ( $this->services as $service ) {
			$service->register_hooks();
		}
	}
}
