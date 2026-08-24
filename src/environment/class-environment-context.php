<?php
/**
 * Represents the environment selected by WordPress.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Environment;

/**
 * Immutable environment value object.
 */
final readonly class Environment_Context {

	public const TYPE_LOCAL = 'local';

	public const TYPE_DEVELOPMENT = 'development';

	public const TYPE_STAGING = 'staging';

	public const TYPE_PRODUCTION = 'production';

	public const SOURCE_CONSTANT = 'constant';

	public const SOURCE_ENVIRONMENT = 'environment-variable';

	public const SOURCE_DEFAULT = 'wordpress-default';

	/**
	 * Environment values accepted by WordPress core.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_TYPES = array(
		self::TYPE_LOCAL,
		self::TYPE_DEVELOPMENT,
		self::TYPE_STAGING,
		self::TYPE_PRODUCTION,
	);

	/**
	 * Normalized environment type.
	 *
	 * @var string
	 */
	private string $type;

	/**
	 * Source of the environment value.
	 *
	 * @var string
	 */
	private string $source;

	/**
	 * Whether the emergency kill switch is active.
	 *
	 * @var bool
	 */
	private bool $emergency_disabled;

	/**
	 * Creates an environment context.
	 *
	 * Unknown values normalize to WordPress's production default, where this
	 * plugin intentionally remains inert.
	 *
	 * @param string $type               Environment type.
	 * @param string $source             Source of the environment value.
	 * @param bool   $emergency_disabled Whether the emergency kill switch is active.
	 */
	public function __construct(
		string $type,
		string $source = self::SOURCE_DEFAULT,
		bool $emergency_disabled = false
	) {
		$is_known_type = in_array( $type, self::ALLOWED_TYPES, true );

		$this->type               = $is_known_type ? $type : self::TYPE_PRODUCTION;
		$this->source             = $is_known_type ? $source : self::SOURCE_DEFAULT;
		$this->emergency_disabled = $emergency_disabled;
	}

	/**
	 * Returns the normalized environment type.
	 */
	public function type(): string {
		return $this->type;
	}

	/**
	 * Returns how the environment was configured.
	 */
	public function source(): string {
		return $this->source;
	}

	/**
	 * Returns whether WordPress considers this production.
	 */
	public function is_production(): bool {
		return self::TYPE_PRODUCTION === $this->type;
	}

	/**
	 * Returns whether non-production protections may run.
	 */
	public function is_guarded(): bool {
		return ! $this->is_production() && ! $this->emergency_disabled;
	}

	/**
	 * Returns whether the emergency kill switch is active.
	 */
	public function is_emergency_disabled(): bool {
		return $this->emergency_disabled;
	}
}
