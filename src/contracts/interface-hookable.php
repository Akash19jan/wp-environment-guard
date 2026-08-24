<?php
/**
 * Defines objects that register WordPress hooks.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Contracts;

/**
 * Contract for a WordPress hook adapter.
 */
interface Hookable {

	/**
	 * Registers the object's WordPress hooks.
	 */
	public function register_hooks(): void;
}
