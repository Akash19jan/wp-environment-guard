<?php
/**
 * Tests immutable policy decisions.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Policy\Decision;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the decision factory contract.
 */
#[CoversClass( Decision::class )]
final class Decision_Test extends TestCase {

	/**
	 * Allow decisions expose a stable reason and no target.
	 */
	public function test_allow_decision(): void {
		$decision = Decision::allow( 'production_inert' );

		self::assertSame( Decision::ACTION_ALLOW, $decision->action() );
		self::assertSame( 'production_inert', $decision->reason() );
		self::assertNull( $decision->target() );
		self::assertTrue( $decision->is_allowed() );
		self::assertFalse( $decision->is_blocked() );
		self::assertFalse( $decision->is_redirected() );
	}

	/**
	 * Redirect decisions carry only the explicit safe target.
	 */
	public function test_redirect_decision(): void {
		$decision = Decision::redirect( 'mail_redirected', 'sink@example.test' );

		self::assertSame( Decision::ACTION_REDIRECT, $decision->action() );
		self::assertSame( 'sink@example.test', $decision->target() );
		self::assertTrue( $decision->is_redirected() );
	}

	/**
	 * Observe is distinct from allow so adapters can emit diagnostics.
	 */
	public function test_observe_decision(): void {
		$decision = Decision::observe( 'http_observed' );

		self::assertSame( Decision::ACTION_OBSERVE, $decision->action() );
		self::assertFalse( $decision->is_allowed() );
		self::assertFalse( $decision->is_blocked() );
	}

	/**
	 * Redirect decisions reject an empty destination.
	 */
	public function test_redirect_requires_a_non_empty_target(): void {
		$this->expectException( InvalidArgumentException::class );

		Decision::redirect( 'mail_redirected', '  ' );
	}
}
