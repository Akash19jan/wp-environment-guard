<?php
/**
 * Tests the WordPress mail adapter.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Tests\Unit;

use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Integration\Mail_Guard;
use Akash_Aagrawal\Environment_Guard\Policy\Mail_Policy;
use Akash_Aagrawal\Environment_Guard\Settings\Settings;
use Akash_Aagrawal\Environment_Guard\Support\Mail_Header_Sanitizer;
use Akash_Aagrawal\Environment_Guard\Support\Policy_Event_Emitter;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Verifies short-circuit and safe-sink behavior at the WordPress boundary.
 */
#[CoversClass( Mail_Guard::class )]
final class Mail_Guard_Test extends Policy_Test_Case {

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
	 * An earlier plugin's false result is authoritative despite being falsey.
	 */
	public function test_preempt_mail_preserves_an_earlier_false_result(): void {
		$guard = $this->guard( $this->environment(), $this->settings() );

		self::assertFalse( $guard->preempt_mail( false ) );
		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_action',
			$GLOBALS
		);
	}

	/**
	 * Block mode reports a successful no-op and emits no message payload.
	 */
	public function test_block_mode_short_circuits_mail_as_handled(): void {
		$guard      = $this->guard( $this->environment(), $this->settings() );
		$attributes = array(
			'to'      => 'customer@example.test',
			'subject' => 'Private subject',
			'message' => 'Private message',
		);

		self::assertTrue( $guard->preempt_mail( null ) );
		self::assertSame( $attributes, $guard->filter_mail( $attributes ) );
		self::assertSame(
			array(
				'hook' => 'akash_aagrawal_environment_guard_policy_decision',
				'args' => array( 'mail', 'block', 'mail_blocked' ),
			),
			$GLOBALS['akash_aagrawal_environment_guard_test_action']
		);
	}

	/**
	 * Redirect mode replaces recipients, prefixes subjects, and strips headers.
	 */
	public function test_redirect_strips_whitespace_prefixed_bcc_from_string_headers(): void {
		$guard = $this->redirect_guard();
		$mail  = array(
			'to'          => 'customer@example.test',
			'subject'     => 'Order update',
			'message'     => 'Private message',
			'headers'     => implode(
				"\r\n",
				array(
					'Content-Type: text/html',
					" \tBcc: hidden@example.test",
					'Cc: copied@example.test',
					'Reply-To: reply@example.test',
					'X-Application: environment-guard-test',
				)
			),
			'attachments' => array( '/tmp/example.txt' ),
		);

		$filtered = $guard->filter_mail( $mail );

		self::assertSame( 'sink@example.test', $filtered['to'] );
		self::assertSame( '[STAGING] Order update', $filtered['subject'] );
		self::assertSame( 'Private message', $filtered['message'] );
		self::assertSame( array( '/tmp/example.txt' ), $filtered['attachments'] );
		self::assertSame(
			"Content-Type: text/html\nX-Application: environment-guard-test",
			$filtered['headers']
		);
		self::assertNull( $guard->preempt_mail( null ) );
	}

	/**
	 * Indexed header arrays reject whitespace-prefixed recipient headers too.
	 */
	public function test_redirect_strips_whitespace_prefixed_bcc_from_indexed_headers(): void {
		$mail = array(
			'to'      => 'customer@example.test',
			'subject' => 'Order update',
			'message' => 'Private message',
			'headers' => array(
				" \tBcc: hidden@example.test",
				'Content-Type: text/plain',
				"\tReply-To: reply@example.test",
				'X-Application: environment-guard-test',
			),
		);

		$filtered = $this->redirect_guard()->filter_mail( $mail );

		self::assertSame(
			array(
				1 => 'Content-Type: text/plain',
				3 => 'X-Application: environment-guard-test',
			),
			$filtered['headers']
		);
	}

	/**
	 * Production passes restrictive mail configuration through unchanged.
	 */
	public function test_production_is_inert(): void {
		$environment = $this->environment( Environment_Context::TYPE_PRODUCTION );
		$settings    = $this->settings(
			array(
				'mail_mode'             => Settings::MAIL_MODE_REDIRECT,
				'mail_redirect_address' => 'sink@example.test',
			)
		);
		$guard       = $this->guard( $environment, $settings );
		$mail        = array(
			'to'      => 'customer@example.test',
			'subject' => 'Production subject',
			'message' => 'Production message',
			'headers' => "Bcc: production@example.test\nX-Application: production",
		);

		self::assertSame( $mail, $guard->filter_mail( $mail ) );
		self::assertNull( $guard->preempt_mail( null ) );
		self::assertArrayNotHasKey(
			'akash_aagrawal_environment_guard_test_action',
			$GLOBALS
		);
	}

	/**
	 * Creates a redirecting staging mail adapter.
	 */
	private function redirect_guard(): Mail_Guard {
		return $this->guard(
			$this->environment(),
			$this->settings(
				array(
					'mail_mode'             => Settings::MAIL_MODE_REDIRECT,
					'mail_redirect_address' => 'sink@example.test',
				)
			)
		);
	}

	/**
	 * Creates a mail adapter with real policy collaborators.
	 *
	 * @param Environment_Context $environment Current environment.
	 * @param Settings            $settings    Validated settings.
	 */
	private function guard( Environment_Context $environment, Settings $settings ): Mail_Guard {
		return new Mail_Guard(
			new Mail_Policy( $environment, $settings ),
			$environment,
			new Mail_Header_Sanitizer(),
			new Policy_Event_Emitter()
		);
	}
}
