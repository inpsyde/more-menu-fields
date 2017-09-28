<?php declare( strict_types=1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the more-menu-fields package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\MoreMenuFields;

/**
 * @package more-menu-fields
 * @license http://opensource.org/licenses/MIT MIT
 */
class EditFieldValue {

	const KEY_PREFIX = '_inpsyde_menu_edit_';

	/**
	 * @var string
	 */
	private $meta_key;

	/**
	 * @var int
	 */
	private $item_id;

	/**
	 * @var callable|null
	 */
	private $sanitize_callback;

	/**
	 * @param string        $key
	 * @param int           $item_id
	 * @param callable|null $sanitize_callback
	 */
	public function __construct( string $key, int $item_id, callable $sanitize_callback = null ) {

		$this->meta_key          = self::KEY_PREFIX . $key;
		$this->item_id           = $item_id && is_nav_menu_item( $item_id ) ? $item_id : 0;
		$this->sanitize_callback = $sanitize_callback;
	}

	/**
	 * @return bool
	 */
	public function is_valid(): bool {

		return $this->item_id > 0;
	}

	/**
	 * @return string
	 */
	public function form_field_name(): string {

		return "{$this->meta_key}[{$this->item_id}]";
	}

	/**
	 * @return string
	 */
	public function form_field_id(): string {

		return "{$this->meta_key}-{$this->item_id}";
	}

	/**
	 * @return string
	 */
	public function form_field_class(): string {

		$class = sanitize_html_class( substr( $this->meta_key, strlen( self::KEY_PREFIX ) ) );

		return "field-{$class} description description-wide";
	}

	/**
	 * @return int
	 */
	public function item_id(): int {

		return $this->item_id;
	}

	/**
	 * @return mixed
	 */
	public function value() {

		$value = get_post_meta( $this->item_id, $this->meta_key, TRUE );
		$this->sanitize_callback and $value = ( $this->sanitize_callback )( $value );

		return $value;
	}

	/**
	 * @return bool
	 */
	public function save(): bool {

		if ( ! doing_action( 'wp_update_nav_menu_item' ) ) {
			return FALSE;
		}

		$values = filter_input( INPUT_POST, $this->meta_key, FILTER_UNSAFE_RAW, FILTER_FORCE_ARRAY );
		if ( ! is_array( $values ) ) {
			$values = filter_input( INPUT_GET, $this->meta_key, FILTER_UNSAFE_RAW, FILTER_FORCE_ARRAY );
		}

		$valid = is_array( $values ) && array_key_exists( $this->item_id, $values );
		$value = $valid ? $values[ $this->item_id ] : null;
		$now   = $this->value();

		$this->sanitize_callback and $value = ( $this->sanitize_callback )( $value );

		if ( $value === $now ) {
			return TRUE;
		}

		if ( $value ) {
			return (bool) update_post_meta( $this->item_id, $this->meta_key, $value );
		} elseif ( $now ) {
			return (bool) delete_post_meta( $this->item_id, $this->meta_key );
		}

		return TRUE;

	}
}