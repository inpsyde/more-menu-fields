<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the more-menu-fields package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package more-menu-fields
 * @license http://opensource.org/licenses/MIT MIT
 */
class Walker_Nav_Menu_Edit {

	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {

		$output .= "<li><div id=\"menu-item-settings-{$item->ID}\"><fieldset></fieldset></div>";
	}

}