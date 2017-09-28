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
class EditFieldValueFactory {

	/**
	 * @var int
	 */
	private $item_id;

	/**
	 * @param int $item_id
	 */
	public function __construct( int $item_id ) {

		$this->item_id = $item_id;
	}

	/**
	 * @param string        $key
	 * @param callable|null $sanitize_callback
	 *
	 * @return EditFieldValue
	 */
	public function create( string $key, callable $sanitize_callback = null ): EditFieldValue {

		return new EditFieldValue( $key, $this->item_id, $sanitize_callback );

	}
}