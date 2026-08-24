<?php
/**
 * Defines access to environment guard settings.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Settings;

/**
 * Supplies validated settings.
 */
interface Settings_Provider {

	/**
	 * Returns the current settings value object.
	 */
	public function get(): Settings;
}
