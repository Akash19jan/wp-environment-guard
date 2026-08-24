<?php
/**
 * Tests Composer dependency enforcement at the plugin boundary.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Lifecycle\Dependency_Guard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Verifies incomplete packages cannot remain silently active.
 */
#[CoversClass( Dependency_Guard::class )]
final class Dependency_Guard_Test extends TestCase {

	/**
	 * Clears lifecycle shim state before each test.
	 */
	protected function setUp(): void {
		$this->reset_test_state();
	}

	/**
	 * Clears lifecycle shim state after each test.
	 */
	protected function tearDown(): void {
		$this->reset_test_state();
	}

	/**
	 * A readable autoloader permits activation and needs no recovery hooks.
	 */
	public function test_readable_autoloader_is_ready(): void {
		$guard = new Dependency_Guard( '/plugins/example/example.php', __FILE__ );

		$guard->register_hooks();

		self::assertTrue( $guard->is_ready() );
		self::assertArrayHasKey(
			'akash_aagrawal_environment_guard_test_activation_hook',
			$GLOBALS
		);
		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_registered_actions',
			$GLOBALS
		);
	}

	/**
	 * Missing dependencies fail activation with actionable guidance.
	 */
	public function test_missing_autoloader_blocks_activation(): void {
		$guard = $this->missing_guard();

		self::assertFalse( $guard->is_ready() );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Composer autoloader is missing' );

		$guard->assert_ready_for_activation();
	}

	/**
	 * An already-active incomplete copy registers recovery behavior.
	 */
	public function test_missing_autoloader_registers_recovery_hooks(): void {
		$this->missing_guard()->register_hooks();

		self::assertArrayHasKey(
			'akash_aagrawal_environment_guard_test_activation_hook',
			$GLOBALS
		);
		self::assertSame(
			array( 'admin_init', 'admin_notices' ),
			array_keys( $GLOBALS['akash_aagrawal_environment_guard_test_registered_actions'] )
		);
	}

	/**
	 * Recovery silently deactivates only this incomplete plugin copy.
	 */
	public function test_missing_installation_self_deactivates(): void {
		$this->missing_guard()->deactivate_incomplete_installation();

		self::assertSame(
			array(
				'plugin'       => 'wp-environment-guard/wp-environment-guard.php',
				'silent'       => true,
				'network_wide' => false,
			),
			$GLOBALS['akash_aagrawal_environment_guard_test_deactivated_plugin']
		);
	}

	/**
	 * Recovery exposes an accessible, actionable administration notice.
	 */
	public function test_missing_installation_renders_recovery_notice(): void {
		ob_start();
		$this->missing_guard()->render_admin_notice();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'role="alert"', $output );
		self::assertStringContainsString( 'was deactivated', $output );
		self::assertStringContainsString( 'Composer autoloader is missing', $output );
	}

	/**
	 * Creates a guard whose expected autoloader cannot exist.
	 */
	private function missing_guard(): Dependency_Guard {
		return new Dependency_Guard(
			'/plugins/wp-environment-guard/wp-environment-guard.php',
			'/definitely-missing/wp-environment-guard/vendor/autoload.php'
		);
	}

	/**
	 * Clears lifecycle global state used only by test shims.
	 */
	private function reset_test_state(): void {
		unset(
			$GLOBALS['akash_aagrawal_environment_guard_test_activation_hook'],
			$GLOBALS['akash_aagrawal_environment_guard_test_registered_actions'],
			$GLOBALS['akash_aagrawal_environment_guard_test_deactivated_plugin']
		);
	}
}
