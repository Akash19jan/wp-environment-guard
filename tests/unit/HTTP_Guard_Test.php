<?php
/**
 * Tests the WordPress HTTP API adapter.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Integration\HTTP_Guard;
use Akash_Aagrawal\Environment_Guard\Policy\HTTP_Policy;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use Akash_Aagrawal\Environment_Guard\Support\Host_Matcher;
use Akash_Aagrawal\Environment_Guard\Support\Policy_Event_Emitter;
use PHPUnit\Framework\Attributes\CoversClass;
use WP_Error;

/**
 * Verifies HTTP short-circuit and redirect behavior at the WordPress boundary.
 */
#[CoversClass( HTTP_Guard::class )]
final class HTTP_Guard_Test extends Policy_Test_Case {

	/**
	 * Clears captured policy actions before each test.
	 */
	protected function setUp(): void {
		unset( $GLOBALS['akash_aagrawal_environment_guard_test_action'] );
	}

	/**
	 * Clears captured policy actions after each test.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['akash_aagrawal_environment_guard_test_action'] );
	}

	/**
	 * An earlier HTTP short-circuit response is authoritative.
	 */
	public function test_preempt_request_preserves_an_earlier_response(): void {
		$earlier = array(
			'headers'  => array(),
			'body'     => '',
			'response' => array(
				'code'    => 204,
				'message' => 'No Content',
			),
			'cookies'  => array(),
			'filename' => null,
		);
		$guard   = $this->allowlist_guard( $this->environment() );

		self::assertSame(
			$earlier,
			$guard->preempt_request( $earlier, array(), 'https://blocked.example.test/private' )
		);
		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_action',
			$GLOBALS
		);
	}

	/**
	 * A blocked host returns a generic WP_Error and privacy-safe event.
	 */
	public function test_allowlist_returns_error_for_blocked_host(): void {
		$result = $this->allowlist_guard( $this->environment() )->preempt_request(
			false,
			array( 'blocking' => false ),
			'https://blocked.example.test/private?token=secret'
		);

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'akash_aagrawal_environment_guard_http_blocked', $result->get_error_code() );
		self::assertSame( 'Outbound request blocked by the environment policy.', $result->get_error_message() );
		self::assertSame(
			array(
				'hook' => 'akash_aagrawal_environment_guard_policy_decision',
				'args' => array( 'http', 'block', 'http_host_blocked' ),
			),
			$GLOBALS['akash_aagrawal_environment_guard_test_action']
		);
	}

	/**
	 * Allowlist enforcement disables redirects without changing other arguments.
	 */
	public function test_allowlist_suppresses_http_redirects(): void {
		$arguments = array(
			'redirection' => 5,
			'blocking'    => false,
			'user-agent'  => 'environment-guard-test',
		);

		$filtered = $this->allowlist_guard( $this->environment() )->filter_request_arguments( $arguments );

		self::assertSame( 0, $filtered['redirection'] );
		self::assertFalse( $filtered['blocking'] );
		self::assertSame( 'environment-guard-test', $filtered['user-agent'] );
	}

	/**
	 * Observe mode leaves the request and redirect budget unchanged.
	 */
	public function test_observe_mode_allows_request_after_safe_event(): void {
		$guard     = $this->guard( $this->environment(), $this->settings() );
		$arguments = array( 'redirection' => 3 );

		self::assertSame( $arguments, $guard->filter_request_arguments( $arguments ) );
		self::assertFalse(
			$guard->preempt_request( false, array(), 'https://observed.example.test/private' )
		);
		self::assertSame(
			array(
				'hook' => 'akash_aagrawal_environment_guard_policy_decision',
				'args' => array( 'http', 'observe', 'http_observed' ),
			),
			$GLOBALS['akash_aagrawal_environment_guard_test_action']
		);
	}

	/**
	 * Production does not block hosts or change redirect behavior.
	 */
	public function test_production_is_inert(): void {
		$guard     = $this->allowlist_guard(
			$this->environment( Environment_Context::TYPE_PRODUCTION )
		);
		$arguments = array(
			'redirection' => 5,
			'blocking'    => false,
		);

		self::assertSame( $arguments, $guard->filter_request_arguments( $arguments ) );
		self::assertFalse(
			$guard->preempt_request( false, array(), 'https://blocked.example.test/private' )
		);
		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_action',
			$GLOBALS
		);
	}

	/**
	 * Creates an exact-host allowlist adapter.
	 *
	 * @param Environment_Context $environment Current environment.
	 */
	private function allowlist_guard( Environment_Context $environment ): HTTP_Guard {
		return $this->guard(
			$environment,
			$this->settings(
				array(
					'http_mode'          => Settings::HTTP_MODE_ALLOWLIST,
					'http_allowed_hosts' => array( 'allowed.example.test' ),
				)
			)
		);
	}

	/**
	 * Creates an HTTP adapter with real policy collaborators.
	 *
	 * @param Environment_Context $environment Current environment.
	 * @param Settings            $settings    Validated settings.
	 */
	private function guard( Environment_Context $environment, Settings $settings ): HTTP_Guard {
		return new HTTP_Guard(
			new HTTP_Policy(
				$environment,
				$settings,
				new Host_Matcher( $settings->http_allowed_hosts() )
			),
			new Policy_Event_Emitter()
		);
	}
}
