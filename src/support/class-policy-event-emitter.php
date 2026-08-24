<?php
/**
 * Emits privacy-safe policy decision events.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Support;

use Akash_Aagrawal\Environment_Guard\Policy\Decision;

/**
 * Exposes decision metadata without persisting request payloads.
 */
final class Policy_Event_Emitter {

	/**
	 * Emits a policy decision for optional external observers.
	 *
	 * The action intentionally contains no email addresses, URLs, payloads,
	 * headers, customer identifiers, or other request data.
	 *
	 * @param string   $policy   Stable policy ID.
	 * @param Decision $decision Policy decision.
	 */
	public function emit( string $policy, Decision $decision ): void {
		do_action(
			'akash_aagrawal_environment_guard_policy_decision',
			$policy,
			$decision->action(),
			$decision->reason()
		);
	}
}
