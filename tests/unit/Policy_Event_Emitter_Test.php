<?php
/**
 * Tests privacy-safe policy events.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Policy\Decision;
use Akash_Aagrawal\Environment_Guard\Support\Policy_Event_Emitter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Verifies events expose identifiers but not decision targets.
 */
#[CoversClass( Policy_Event_Emitter::class )]
final class Policy_Event_Emitter_Test extends TestCase {

	/**
	 * Clears captured action state after each test.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['akash_aagrawal_environment_guard_test_action'] );
	}

	/**
	 * A redirect target is intentionally omitted from the event.
	 */
	public function test_event_contains_only_safe_low_cardinality_values(): void {
		$decision = Decision::redirect( 'mail_redirected', 'sensitive-sink@example.test' );

		( new Policy_Event_Emitter() )->emit( 'mail', $decision );

		self::assertSame(
			array(
				'hook' => 'akash_aagrawal_environment_guard_policy_decision',
				'args' => array( 'mail', 'redirect', 'mail_redirected' ),
			),
			$GLOBALS['akash_aagrawal_environment_guard_test_action']
		);
		self::assertNotContains(
			'sensitive-sink@example.test',
			$GLOBALS['akash_aagrawal_environment_guard_test_action']['args']
		);
	}
}
