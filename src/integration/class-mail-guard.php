<?php
/**
 * Integrates the outbound mail policy with WordPress.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Integration;

use Akash_Aagrawal\Environment_Guard\Contracts\Hookable;
use Akash_Aagrawal\Environment_Guard\Environment\Environment_Context;
use Akash_Aagrawal\Environment_Guard\Policy\Mail_Policy;
use Akash_Aagrawal\Environment_Guard\Support\Mail_Header_Sanitizer;
use Akash_Aagrawal\Environment_Guard\Support\Policy_Event_Emitter;

/**
 * Blocks or safely redirects mail from guarded environments.
 */
final class Mail_Guard implements Hookable {

	/**
	 * Creates the WordPress mail adapter.
	 *
	 * @param Mail_Policy           $policy           Mail policy.
	 * @param Environment_Context   $environment      Current environment.
	 * @param Mail_Header_Sanitizer $header_sanitizer Sensitive header sanitizer.
	 * @param Policy_Event_Emitter  $event_emitter    Privacy-safe event emitter.
	 */
	public function __construct(
		private readonly Mail_Policy $policy,
		private readonly Environment_Context $environment,
		private readonly Mail_Header_Sanitizer $header_sanitizer,
		private readonly Policy_Event_Emitter $event_emitter
	) {}

	/**
	 * Registers WordPress mail filters.
	 */
	public function register_hooks(): void {
		add_filter( 'wp_mail', array( $this, 'filter_mail' ), PHP_INT_MAX );
		add_filter( 'pre_wp_mail', array( $this, 'preempt_mail' ), PHP_INT_MAX, 1 );
	}

	/**
	 * Redirects a message to the configured sink and removes unsafe headers.
	 *
	 * @param array<string, mixed> $attributes WordPress mail attributes.
	 * @return array<string, mixed> Filtered mail attributes.
	 */
	public function filter_mail( array $attributes ): array {
		$decision = $this->policy->decide();

		if ( ! $decision->is_redirected() ) {
			return $attributes;
		}

		$target = $decision->target();

		if ( null === $target ) {
			return $attributes;
		}

		$attributes['to'] = $target;

		if ( isset( $attributes['headers'] ) && ( is_array( $attributes['headers'] ) || is_string( $attributes['headers'] ) ) ) {
			$attributes['headers'] = $this->header_sanitizer->sanitize( $attributes['headers'] );
		}

		$subject               = isset( $attributes['subject'] ) && is_string( $attributes['subject'] )
			? $attributes['subject']
			: '';
		$attributes['subject'] = $this->environment_prefix() . $subject;

		$this->event_emitter->emit( 'mail', $decision );

		return $attributes;
	}

	/**
	 * Short-circuits blocked mail as a successful no-op.
	 *
	 * Returning an earlier non-null value preserves another filter's decision.
	 *
	 * @param mixed $preempt Previously filtered short-circuit value.
	 * @return mixed True when blocked; the previous value otherwise.
	 */
	public function preempt_mail( mixed $preempt ): mixed {
		if ( null !== $preempt ) {
			return $preempt;
		}

		$decision = $this->policy->decide();

		if ( ! $decision->is_blocked() ) {
			return $preempt;
		}

		$this->event_emitter->emit( 'mail', $decision );

		return true;
	}

	/**
	 * Returns a visible environment prefix for redirected message subjects.
	 */
	private function environment_prefix(): string {
		return '[' . strtoupper( $this->environment->type() ) . '] ';
	}
}
