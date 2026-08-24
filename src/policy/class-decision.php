<?php
/**
 * Represents a pure policy decision.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Policy;

use InvalidArgumentException;

/**
 * Immutable decision returned by policy classes.
 */
final readonly class Decision {

	public const ACTION_ALLOW = 'allow';

	public const ACTION_BLOCK = 'block';

	public const ACTION_REDIRECT = 'redirect';

	public const ACTION_OBSERVE = 'observe';

	/**
	 * Creates a policy decision.
	 *
	 * @param string      $action Stable action identifier.
	 * @param string      $reason Stable reason code.
	 * @param string|null $target Optional safe redirect target.
	 * @throws InvalidArgumentException When a redirect has no target.
	 */
	private function __construct(
		private string $action,
		private string $reason,
		private ?string $target = null
	) {
		if ( self::ACTION_REDIRECT === $this->action
			&& ( null === $this->target || '' === trim( $this->target ) )
		) {
			throw new InvalidArgumentException( 'Redirect decisions require a target.' );
		}
	}

	/**
	 * Creates an allow decision.
	 *
	 * @param string $reason Stable reason code.
	 */
	public static function allow( string $reason ): self {
		return new self( self::ACTION_ALLOW, $reason );
	}

	/**
	 * Creates a block decision.
	 *
	 * @param string $reason Stable reason code.
	 */
	public static function block( string $reason ): self {
		return new self( self::ACTION_BLOCK, $reason );
	}

	/**
	 * Creates a redirect decision.
	 *
	 * @param string $reason Stable reason code.
	 * @param string $target Validated redirect target.
	 */
	public static function redirect( string $reason, string $target ): self {
		return new self( self::ACTION_REDIRECT, $reason, $target );
	}

	/**
	 * Creates an observe-only decision.
	 *
	 * @param string $reason Stable reason code.
	 */
	public static function observe( string $reason ): self {
		return new self( self::ACTION_OBSERVE, $reason );
	}

	/**
	 * Returns the action identifier.
	 */
	public function action(): string {
		return $this->action;
	}

	/**
	 * Returns the stable reason code.
	 */
	public function reason(): string {
		return $this->reason;
	}

	/**
	 * Returns the redirect target when applicable.
	 */
	public function target(): ?string {
		return $this->target;
	}

	/**
	 * Returns whether this decision permits the operation.
	 */
	public function is_allowed(): bool {
		return self::ACTION_ALLOW === $this->action;
	}

	/**
	 * Returns whether this decision blocks the operation.
	 */
	public function is_blocked(): bool {
		return self::ACTION_BLOCK === $this->action;
	}

	/**
	 * Returns whether this decision redirects the operation.
	 */
	public function is_redirected(): bool {
		return self::ACTION_REDIRECT === $this->action;
	}
}
