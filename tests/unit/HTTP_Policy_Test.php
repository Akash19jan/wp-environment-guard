<?php
/**
 * Tests outbound HTTP decisions.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Policy\HTTP_Policy;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use Akash_Aagrawal\Environment_Guard\Support\Host_Matcher;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Verifies observe mode and exact-host enforcement.
 */
#[CoversClass( HTTP_Policy::class )]
#[CoversClass( Host_Matcher::class )]
final class HTTP_Policy_Test extends Policy_Test_Case {

	/**
	 * Observe mode records a decision but permits the request.
	 */
	public function test_staging_observes_by_default(): void {
		$policy   = $this->policy( $this->settings() );
		$decision = $policy->decide( 'https://api.example.test/v1' );

		self::assertSame( 'observe', $decision->action() );
		self::assertSame( 'http_observed', $decision->reason() );
		self::assertFalse( $policy->prevents_redirects() );
	}

	/**
	 * Allowlist mode accepts an exact host regardless of case or trailing dot.
	 */
	public function test_allowlist_accepts_exact_normalized_host(): void {
		$settings = $this->settings(
			array(
				'http_mode'          => Settings::HTTP_MODE_ALLOWLIST,
				'http_allowed_hosts' => array( 'api.example.test' ),
			)
		);
		$policy   = $this->policy( $settings );

		$decision = $policy->decide( 'https://API.EXAMPLE.TEST./v1' );

		self::assertTrue( $decision->is_allowed() );
		self::assertSame( 'http_host_allowed', $decision->reason() );
		self::assertTrue( $policy->prevents_redirects() );
	}

	/**
	 * Exact matching does not implicitly trust subdomains.
	 */
	public function test_allowlist_rejects_subdomain(): void {
		$settings = $this->settings(
			array(
				'http_mode'          => Settings::HTTP_MODE_ALLOWLIST,
				'http_allowed_hosts' => array( 'example.test' ),
			)
		);

		$decision = $this->policy( $settings )->decide( 'https://api.example.test/v1' );

		self::assertTrue( $decision->is_blocked() );
		self::assertSame( 'http_host_blocked', $decision->reason() );
	}

	/**
	 * Malformed and hostless URLs fail safely during enforcement.
	 */
	public function test_allowlist_rejects_hostless_url(): void {
		$settings = $this->settings(
			array(
				'http_mode'          => Settings::HTTP_MODE_ALLOWLIST,
				'http_allowed_hosts' => array( 'example.test' ),
			)
		);

		$decision = $this->policy( $settings )->decide( '/relative/path' );

		self::assertTrue( $decision->is_blocked() );
	}

	/**
	 * Production ignores allowlist enforcement.
	 */
	public function test_production_is_always_allowed(): void {
		$settings = $this->settings( array( 'http_mode' => Settings::HTTP_MODE_ALLOWLIST ) );
		$policy   = new HTTP_Policy(
			$this->environment( Environment_Context::TYPE_PRODUCTION ),
			$settings,
			new Host_Matcher( $settings->http_allowed_hosts() )
		);

		$decision = $policy->decide( 'https://untrusted.example.test' );

		self::assertTrue( $decision->is_allowed() );
		self::assertSame( 'production_inert', $decision->reason() );
		self::assertFalse( $policy->prevents_redirects() );
	}

	/**
	 * Creates a staging HTTP policy with its real matcher.
	 *
	 * @param Settings $settings Validated settings.
	 */
	private function policy( Settings $settings ): HTTP_Policy {
		return new HTTP_Policy(
			$this->environment(),
			$settings,
			new Host_Matcher( $settings->http_allowed_hosts() )
		);
	}
}
