<?php declare( strict_types=1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the more-menu-fields package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\MoreMenuFields\Tests;

use Inpsyde\MoreMenuFields\EditFieldValueFactory;
use Brain\Monkey\Functions;

/**
 * @package more-menu-fields
 * @license http://opensource.org/licenses/MIT MIT
 */
class EditFieldValueFactoryTest extends TestCase {

	public function test_create() {

		Functions\expect( 'is_nav_menu_item' )->with( 123 )->andReturn( TRUE );
		Functions\expect( 'get_post_meta' )->andReturn( '9876' );

		$factory = new EditFieldValueFactory( 123 );
		$value = $factory->create( 'foo', 'intval' );

		static::assertSame( 123, $value->item_id() );
		static::assertSame( 9876, $value->value() );
	}

}