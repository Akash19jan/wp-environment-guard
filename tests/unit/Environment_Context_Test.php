<?php
/**
 * Tests environment context normalization.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Verifies environment normalization and guard eligibility.
 */
#[CoversClass( Environment_Context::class )]
final class Environment_Context_Test extends TestCase {

	/**
	 * Production is valid but never guarded.
	 */
	public function test_production_is_inert(): void {
		$context = new Environment_Context(
			Environment_Context::TYPE_PRODUCTION,
			Environment_Context::SOURCE_CONSTANT
		);

		self::assertSame( Environment_Context::TYPE_PRODUCTION, $context->type() );
		self::assertSame( Environment_Context::SOURCE_CONSTANT, $context->source() );
		self::assertTrue( $context->is_production() );
		self::assertFalse( $context->is_guarded() );
	}

	/**
	 * A known non-production environment enables policies.
	 */
	public function test_staging_is_guarded(): void {
		$context = new Environment_Context(
			Environment_Context::TYPE_STAGING,
			Environment_Context::SOURCE_ENVIRONMENT
		);

		self::assertFalse( $context->is_production() );
		self::assertTrue( $context->is_guarded() );
		self::assertFalse( $context->is_emergency_disabled() );
	}

	/**
	 * Unknown input uses WordPress's production default.
	 */
	public function test_unknown_type_normalizes_to_production(): void {
		$context = new Environment_Context( 'preview', Environment_Context::SOURCE_CONSTANT );

		self::assertSame( Environment_Context::TYPE_PRODUCTION, $context->type() );
		self::assertSame( Environment_Context::SOURCE_DEFAULT, $context->source() );
		self::assertFalse( $context->is_guarded() );
	}

	/**
	 * The emergency switch disables enforcement without changing type.
	 */
	public function test_emergency_switch_disables_guarding(): void {
		$context = new Environment_Context(
			Environment_Context::TYPE_DEVELOPMENT,
			Environment_Context::SOURCE_CONSTANT,
			true
		);

		self::assertSame( Environment_Context::TYPE_DEVELOPMENT, $context->type() );
		self::assertTrue( $context->is_emergency_disabled() );
		self::assertFalse( $context->is_guarded() );
	}
}
