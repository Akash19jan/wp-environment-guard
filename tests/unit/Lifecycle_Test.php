<?php
/**
 * Tests activation and uninstall lifecycle behavior.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Lifecycle\Activator;
use Akash_Aagrawal\Environment_Guard\Lifecycle\Uninstaller;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use Akash_Aagrawal\Environment_Guard\Settings\Settings_Repository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Verifies owned data and explicit single-site scope.
 */
#[CoversClass( Activator::class )]
#[CoversClass( Uninstaller::class )]
final class Lifecycle_Test extends TestCase {

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
	 * Single-site activation installs secure, non-autoloaded defaults.
	 */
	public function test_single_site_activation_installs_defaults(): void {
		Activator::activate();

		self::assertSame(
			array(
				'name'     => Settings_Repository::OPTION_NAME,
				'value'    => Settings::defaults(),
				'autoload' => false,
			),
			$GLOBALS['akash_aagrawal_environment_guard_test_added_option']
		);
	}

	/**
	 * Multisite installations are refused before an option is written.
	 */
	public function test_multisite_activation_is_refused(): void {
		$GLOBALS['akash_aagrawal_environment_guard_test_is_multisite'] = true;

		try {
			Activator::activate();
			self::fail( 'Expected Multisite activation to terminate.' );
		} catch ( RuntimeException $exception ) {
			self::assertStringContainsString( 'single-site WordPress', $exception->getMessage() );
		}

		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_added_option',
			$GLOBALS
		);
	}

	/**
	 * A network-wide activation request is refused even with a mocked site state.
	 */
	public function test_network_wide_activation_is_refused(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'single-site WordPress' );

		Activator::activate( true );
	}

	/**
	 * Uninstall removes exactly the option owned by this plugin.
	 */
	public function test_uninstall_removes_the_owned_option(): void {
		Uninstaller::uninstall();

		self::assertSame(
			Settings_Repository::OPTION_NAME,
			$GLOBALS['akash_aagrawal_environment_guard_test_deleted_option']
		);
	}

	/**
	 * Clears lifecycle global state used only by test shims.
	 */
	private function reset_test_state(): void {
		unset(
			$GLOBALS['akash_aagrawal_environment_guard_test_added_option'],
			$GLOBALS['akash_aagrawal_environment_guard_test_deleted_option'],
			$GLOBALS['akash_aagrawal_environment_guard_test_is_multisite']
		);
	}
}
