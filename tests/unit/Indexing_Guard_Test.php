<?php
/**
 * Tests the WordPress indexing adapter.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Integration\Indexing_Guard;
use Akash_Aagrawal\Environment_Guard\Policy\Indexing_Policy;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use Akash_Aagrawal\Environment_Guard\Support\Policy_Event_Emitter;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Verifies each defense-in-depth indexing surface.
 */
#[CoversClass( Indexing_Guard::class )]
final class Indexing_Guard_Test extends Policy_Test_Case {

	/**
	 * Clears captured policy actions before each test.
	 */
	protected function setUp(): void {
		$this->reset_test_state();
	}

	/**
	 * Clears captured policy actions after each test.
	 */
	protected function tearDown(): void {
		$this->reset_test_state();
	}

	/**
	 * Guarded environments apply every indexing defense and emit once.
	 */
	public function test_guarded_environment_applies_indexing_defenses(): void {
		$guard = $this->guard( $this->environment(), $this->settings() );

		self::assertSame( 0, $guard->filter_blog_public( 'not-preempted' ) );
		self::assertSame(
			array(
				'max-image-preview' => 'large',
				'noarchive'         => true,
				'nofollow'          => true,
				'noindex'           => true,
			),
			$guard->filter_robots( array( 'max-image-preview' => 'large' ) )
		);
		self::assertSame(
			array(
				'Cache-Control' => 'no-cache',
				'X-Robots-Tag'  => 'index, follow, noindex, nofollow, noarchive',
			),
			$guard->filter_headers(
				array(
					'Cache-Control' => 'no-cache',
					'X-Robots-Tag'  => 'index, follow',
				)
			)
		);
		self::assertSame(
			"User-agent: ExampleBot\nDisallow: /private\nUser-agent: *\nDisallow: /\n",
			$guard->filter_robots_txt( "User-agent: ExampleBot\nDisallow: /private\n" )
		);
		self::assertFalse( $guard->filter_sitemaps_enabled( true ) );
		self::assertSame(
			array(
				array(
					'hook' => 'akash_aagrawal_environment_guard_policy_decision',
					'args' => array( 'indexing', 'block', 'non_production_indexing' ),
				),
			),
			$GLOBALS['akash_aagrawal_environment_guard_test_actions']
		);
	}

	/**
	 * A pre-existing catch-all directive is not duplicated.
	 */
	public function test_robots_txt_preserves_existing_catch_all_rule(): void {
		$robots = "User-agent: *\nDisallow: /\n";

		self::assertSame(
			$robots,
			$this->guard( $this->environment(), $this->settings() )->filter_robots_txt( $robots )
		);
	}

	/**
	 * Production passes all indexing values through unchanged and emits nothing.
	 */
	public function test_production_is_inert(): void {
		$guard   = $this->guard(
			$this->environment( Environment_Context::TYPE_PRODUCTION ),
			$this->settings()
		);
		$robots  = array( 'index' => true );
		$headers = array( 'X-Robots-Tag' => 'index, follow' );

		self::assertSame( 'existing-value', $guard->filter_blog_public( 'existing-value' ) );
		self::assertSame( $robots, $guard->filter_robots( $robots ) );
		self::assertSame( $headers, $guard->filter_headers( $headers ) );
		self::assertSame( "User-agent: *\nAllow: /\n", $guard->filter_robots_txt( "User-agent: *\nAllow: /\n" ) );
		self::assertTrue( $guard->filter_sitemaps_enabled( true ) );
		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_action',
			$GLOBALS
		);
	}

	/**
	 * Disabling the indexing policy passes values through on staging too.
	 */
	public function test_disabled_policy_is_inert_on_staging(): void {
		$guard = $this->guard(
			$this->environment(),
			$this->settings( array( 'protect_indexing' => false ) )
		);

		self::assertSame( 1, $guard->filter_blog_public( 1 ) );
		self::assertTrue( $guard->filter_sitemaps_enabled( true ) );
		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_action',
			$GLOBALS
		);
	}

	/**
	 * Creates the indexing adapter with its real policy.
	 *
	 * @param Environment_Context $environment Current environment.
	 * @param Settings            $settings    Validated settings.
	 */
	private function guard( Environment_Context $environment, Settings $settings ): Indexing_Guard {
		return new Indexing_Guard(
			new Indexing_Policy( $environment, $settings ),
			new Policy_Event_Emitter()
		);
	}

	/**
	 * Clears action-capture state used by the isolated hook shim.
	 */
	private function reset_test_state(): void {
		unset(
			$GLOBALS['akash_aagrawal_environment_guard_test_action'],
			$GLOBALS['akash_aagrawal_environment_guard_test_actions']
		);
	}
}
