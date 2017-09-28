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
final class EditWalker extends \Walker_Nav_Menu_Edit {

	/**
	 * @param string $output
	 * @param object $item
	 * @param int    $depth
	 * @param array  $args
	 * @param int    $id
	 */
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {

		if ( ! isset( $item->ID ) ) {
			parent::start_el( $output, $item, $depth, $args, $id );

			return;
		}

		$fields = ( new EditFields() )->all_fields( (int) $item->ID );

		if ( ! $fields ) {
			parent::start_el( $output, $item, $depth, $args, $id );

			return;
		}

		$el = '';
		parent::start_el( $el, $item, $depth, $args, $id );

		$dom = new \DOMDocument;
		$dom->loadXML( "{$el}</li>" ); // append </li>, or invalid HTML will cause issues, will be removed later

		$item_id = (int) $item->ID;
		$nodes   = $dom->getElementsByTagName( 'div' );

		if ( ! $nodes->length ) {
			$output .= $el;

			return;
		}

		$fieldset = $target_node = null;

		/** @var \DOMElement $node */
		foreach ( $nodes as $node ) {
			if ( $node->getAttribute( 'id' ) === "menu-item-settings-{$item_id}" ) {
				$fieldsets = $node->getElementsByTagName( 'fieldset' );
				$fieldsets->length and $fieldset = $fieldsets->item( 0 );
				$target_node = $node;
				break;
			}
		}

		if ( ! $fieldset ) {
			$output .= $el;

			return;
		}

		$fragment = $dom->createDocumentFragment();
		foreach ( $fields as $field ) {
			$fragment->appendXML( $field->field_markup() );
		}

		$target_node->insertBefore( $fragment, $fieldset );

		$output .= substr( trim( $dom->saveHTML() ), 0, - 5 ); // remove </li> at the end
	}
}