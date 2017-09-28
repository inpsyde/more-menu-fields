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
class EditFields {

	/**
	 * @param int $item_id
	 *
	 * @return EditField[]
	 */
	public function all_fields( int $item_id ): array {

		$fields = apply_filters( FILTER_FIELDS, [], new EditFieldValueFactory( $item_id ) );

		$fields and $fields = array_filter( (array) $fields, function ( $field ) {
			return $field instanceof EditField;
		} );

		return is_array( $fields ) ? $fields : [];
	}

}