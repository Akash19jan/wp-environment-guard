<?php
/**
 * Tests indexing decisions.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Policy\Indexing_Policy;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Verifies production inertness and non-production indexing protection.
 */
#[CoversClass( Indexing_Policy::class )]
final class Indexing_Policy_Test extends Policy_Test_Case {

	/**
	 * Secure defaults block indexing on staging.
	 */
	public function test_staging_is_blocked_by_default(): void {
		$decision = ( new Indexing_Policy( $this->environment(), $this->settings() ) )->decide();

		self::assertTrue( $decision->is_blocked() );
		self::assertSame( 'non_production_indexing', $decision->reason() );
	}

	/**
	 * An explicit setting can disable this non-production protection.
	 */
	public function test_disabled_policy_allows_indexing(): void {
		$decision = ( new Indexing_Policy(
			$this->environment(),
			$this->settings( array( 'protect_indexing' => false ) )
		) )->decide();

		self::assertTrue( $decision->is_allowed() );
		self::assertSame( 'indexing_policy_disabled', $decision->reason() );
	}

	/**
	 * Production remains inert even when protection is enabled.
	 */
	public function test_production_is_always_allowed(): void {
		$decision = ( new Indexing_Policy(
			$this->environment( Environment_Context::TYPE_PRODUCTION ),
			$this->settings()
		) )->decide();

		self::assertTrue( $decision->is_allowed() );
		self::assertSame( 'production_inert', $decision->reason() );
	}
}
