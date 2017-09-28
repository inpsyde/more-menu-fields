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
interface EditField {

	/**
	 * @return string
	 */
	public function name(): string;

	/**
	 * @return string
	 */
	public function field_markup(): string;
}