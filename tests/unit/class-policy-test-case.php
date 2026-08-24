<?php
/**
 * Shared fixtures for pure policy tests.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Builds real immutable value objects for policy tests.
 */
abstract class Policy_Test_Case extends TestCase {

	/**
	 * Creates an environment context.
	 *
	 * @param string $type               WordPress environment type.
	 * @param bool   $emergency_disabled Whether the emergency switch is active.
	 */
	protected function environment(
		string $type = Environment_Context::TYPE_STAGING,
		bool $emergency_disabled = false
	): Environment_Context {
		return new Environment_Context(
			$type,
			Environment_Context::SOURCE_CONSTANT,
			$emergency_disabled
		);
	}

	/**
	 * Creates validated settings from secure defaults and overrides.
	 *
	 * @param array<string, mixed> $overrides Values to override.
	 */
	protected function settings( array $overrides = array() ): Settings {
		return Settings::from_array( array_merge( Settings::defaults(), $overrides ) );
	}
}
