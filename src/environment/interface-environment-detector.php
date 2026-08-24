<?php
/**
 * Defines environment detection.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Environment;

/**
 * Supplies the immutable environment context for a request.
 */
interface Environment_Detector {

	/**
	 * Detects the current WordPress environment.
	 */
	public function detect(): Environment_Context;
}
