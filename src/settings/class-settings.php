<?php
/**
 * Represents validated plugin settings.
 *
 * @package Akash_Aagrawal\Environment_Guard
 */

declare( strict_types=1 );

namespace Akash_Aagrawal\Environment_Guard\Settings;

/**
 * Immutable settings value object.
 */
final readonly class Settings {

	public const MAIL_MODE_ALLOW = 'allow';

	public const MAIL_MODE_BLOCK = 'block';

	public const MAIL_MODE_REDIRECT = 'redirect';

	public const HTTP_MODE_OBSERVE = 'observe';

	public const HTTP_MODE_ALLOWLIST = 'allowlist';

	public const PAYMENT_MODE_ALLOW = 'allow';

	public const PAYMENT_MODE_BLOCK = 'block';

	public const PAYMENT_MODE_ALLOWLIST = 'allowlist';

	/**
	 * Creates the settings value object.
	 *
	 * @param bool   $show_frontend_banner     Whether the public banner is displayed.
	 * @param bool   $protect_indexing         Whether search indexing is blocked.
	 * @param string $mail_mode                Mail policy mode.
	 * @param string $mail_redirect_address    Safe mail sink.
	 * @param string $http_mode                HTTP policy mode.
	 * @param array  $http_allowed_hosts       Exact hosts allowed in enforcement mode.
	 * @param string $payment_mode             WooCommerce payment policy mode.
	 * @param array  $payment_allowed_gateways Safe WooCommerce gateway IDs.
	 * @param bool   $block_webhooks           Whether WooCommerce webhooks are blocked.
	 * @phpstan-param list<string> $http_allowed_hosts Exact hosts allowed in enforcement mode.
	 * @phpstan-param list<string> $payment_allowed_gateways Safe WooCommerce gateway IDs.
	 */
	public function __construct(
		private bool $show_frontend_banner,
		private bool $protect_indexing,
		private string $mail_mode,
		private string $mail_redirect_address,
		private string $http_mode,
		private array $http_allowed_hosts,
		private string $payment_mode,
		private array $payment_allowed_gateways,
		private bool $block_webhooks
	) {}

	/**
	 * Returns secure defaults for non-production environments.
	 *
	 * @return array{
	 *     show_frontend_banner: bool,
	 *     protect_indexing: bool,
	 *     mail_mode: string,
	 *     mail_redirect_address: string,
	 *     http_mode: string,
	 *     http_allowed_hosts: list<string>,
	 *     payment_mode: string,
	 *     payment_allowed_gateways: list<string>,
	 *     block_webhooks: bool
	 * } Default settings.
	 */
	public static function defaults(): array {
		return array(
			'show_frontend_banner'     => true,
			'protect_indexing'         => true,
			'mail_mode'                => self::MAIL_MODE_BLOCK,
			'mail_redirect_address'    => '',
			'http_mode'                => self::HTTP_MODE_OBSERVE,
			'http_allowed_hosts'       => array(
				'api.wordpress.org',
				'downloads.wordpress.org',
				'wordpress.org',
			),
			'payment_mode'             => self::PAYMENT_MODE_ALLOWLIST,
			'payment_allowed_gateways' => array(
				'bacs',
				'cheque',
				'cod',
			),
			'block_webhooks'           => true,
		);
	}

	/**
	 * Builds settings from a normalized option array.
	 *
	 * @param array<string, mixed> $values Option values.
	 */
	public static function from_array( array $values ): self {
		$defaults = self::defaults();
		$values   = array_merge( $defaults, $values );

		return new self(
			self::boolean_value( $values['show_frontend_banner'], true ),
			self::boolean_value( $values['protect_indexing'], true ),
			self::allowed_value(
				$values['mail_mode'],
				array( self::MAIL_MODE_ALLOW, self::MAIL_MODE_BLOCK, self::MAIL_MODE_REDIRECT ),
				self::MAIL_MODE_BLOCK
			),
			is_string( $values['mail_redirect_address'] ) ? trim( $values['mail_redirect_address'] ) : '',
			self::allowed_value(
				$values['http_mode'],
				array( self::HTTP_MODE_OBSERVE, self::HTTP_MODE_ALLOWLIST ),
				self::HTTP_MODE_OBSERVE
			),
			self::string_list( $values['http_allowed_hosts'], $defaults['http_allowed_hosts'] ),
			self::allowed_value(
				$values['payment_mode'],
				array( self::PAYMENT_MODE_ALLOW, self::PAYMENT_MODE_BLOCK, self::PAYMENT_MODE_ALLOWLIST ),
				self::PAYMENT_MODE_ALLOWLIST
			),
			self::string_list( $values['payment_allowed_gateways'], $defaults['payment_allowed_gateways'] ),
			self::boolean_value( $values['block_webhooks'], true )
		);
	}

	/**
	 * Reads an option boolean without treating arbitrary strings as truthy.
	 *
	 * @param mixed $value    Stored option value.
	 * @param bool  $fallback Safe fallback.
	 */
	private static function boolean_value( mixed $value, bool $fallback ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( 0 === $value || 1 === $value ) {
			return 1 === $value;
		}

		return $fallback;
	}

	/**
	 * Selects a stored value from a strict allowlist.
	 *
	 * @param mixed  $value    Stored option value.
	 * @param array  $allowed  Accepted values.
	 * @param string $fallback Safe fallback.
	 * @phpstan-param list<string> $allowed Accepted values.
	 */
	private static function allowed_value( mixed $value, array $allowed, string $fallback ): string {
		return is_string( $value ) && in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	/**
	 * Normalizes a stored list without trusting its element types.
	 *
	 * @param mixed $value    Stored option value.
	 * @param array $fallback Safe default list.
	 * @phpstan-param list<string> $fallback Safe default list.
	 * @return list<string> Normalized, unique values.
	 */
	private static function string_list( mixed $value, array $fallback ): array {
		$value  = is_array( $value ) ? $value : $fallback;
		$values = array();

		foreach ( $value as $item ) {
			if ( ! is_string( $item ) ) {
				continue;
			}

			$item = strtolower( trim( $item ) );

			if ( '' !== $item ) {
				$values[] = $item;
			}
		}

		return array_values( array_unique( $values ) );
	}

	/**
	 * Returns whether the public environment banner is enabled.
	 */
	public function show_frontend_banner(): bool {
		return $this->show_frontend_banner;
	}

	/**
	 * Returns whether indexing protections are enabled.
	 */
	public function protect_indexing(): bool {
		return $this->protect_indexing;
	}

	/**
	 * Returns the configured mail mode.
	 */
	public function mail_mode(): string {
		return $this->mail_mode;
	}

	/**
	 * Returns the validated mail redirect address.
	 */
	public function mail_redirect_address(): string {
		return $this->mail_redirect_address;
	}

	/**
	 * Returns the configured outbound HTTP mode.
	 */
	public function http_mode(): string {
		return $this->http_mode;
	}

	/**
	 * Returns exact allowed HTTP hosts.
	 *
	 * @return list<string> Allowed hosts.
	 */
	public function http_allowed_hosts(): array {
		return $this->http_allowed_hosts;
	}

	/**
	 * Returns the configured WooCommerce payment mode.
	 */
	public function payment_mode(): string {
		return $this->payment_mode;
	}

	/**
	 * Returns allowed WooCommerce gateway IDs.
	 *
	 * @return list<string> Gateway IDs.
	 */
	public function payment_allowed_gateways(): array {
		return $this->payment_allowed_gateways;
	}

	/**
	 * Returns whether WooCommerce webhook delivery is blocked.
	 */
	public function block_webhooks(): bool {
		return $this->block_webhooks;
	}
}
