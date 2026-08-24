<?php
/**
 * Reads environment identity from WordPress core.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Environment;

/**
 * Uses wp_get_environment_type() as the sole environment authority.
 */
final class WordPress_Environment_Detector implements Environment_Detector {

	/**
	 * Detects the current environment once for this request.
	 */
	public function detect(): Environment_Context {
		return new Environment_Context(
			wp_get_environment_type(),
			$this->detect_source(),
			defined( 'AKASH_AAGRAWAL_ENVIRONMENT_GUARD_DISABLED' )
				&& true === constant( 'AKASH_AAGRAWAL_ENVIRONMENT_GUARD_DISABLED' )
		);
	}

	/**
	 * Describes where WordPress found the environment value.
	 */
	private function detect_source(): string {
		if ( defined( 'WP_ENVIRONMENT_TYPE' ) ) {
			return Environment_Context::SOURCE_CONSTANT;
		}

		if ( function_exists( 'getenv' ) && false !== getenv( 'WP_ENVIRONMENT_TYPE' ) ) {
			return Environment_Context::SOURCE_ENVIRONMENT;
		}

		return Environment_Context::SOURCE_DEFAULT;
	}
}
