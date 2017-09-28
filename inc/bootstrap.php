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

if ( defined( 'Inpsyde\MoreMenuFields\FILTER_FIELDS' ) ) {
	return;
}

const FILTER_FIELDS = 'inpsyde.menu-edit-fields';

/**
 * @return void
 */
function bootstrap() {

	static $bootstrapped;

	if ( $bootstrapped ) {
		return;
	}

	$bootstrapped = TRUE;

	add_filter( 'wp_edit_nav_menu_walker', function () {
		return EditWalker::class;
	}, PHP_INT_MAX );

	add_action( 'wp_update_nav_menu_item', function (
		/** @noinspection PhpUnusedParameterInspection */
		$id,
		$db_id
	) {
		$fields  = ( new EditFields() )->all_fields( (int) $db_id );
		$factory = new EditFieldValueFactory( (int) $db_id );
		foreach ( $fields as $field ) {
			$sanitize = $field instanceof SanitizedEditField ? $field->sanitize_callback() : null;
			$value    = $factory->create( $field->name(), $sanitize );
			$value->is_valid() and $value->save();
		}
	}, 10, 2 );
}

/**
 * @param int|\WP_Post $menu_item
 * @param string       $key
 *
 * @return mixed|null
 */
function field_value( $menu_item, string $key ) {

	$post = get_post( $menu_item );

	if ( ! $post instanceof \WP_Post || ! is_nav_menu_item( $post ) ) {
		return null;
	}

	return get_post_meta( $post->ID, EditFieldValue::KEY_PREFIX . $key, TRUE );
}