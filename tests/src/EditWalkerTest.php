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

use Inpsyde\MoreMenuFields\EditField;
use Inpsyde\MoreMenuFields\EditWalker;
use Brain\Monkey\Filters;

use const Inpsyde\MoreMenuFields\FILTER_FIELDS;

/**
 * @package more-menu-fields
 * @license http://opensource.org/licenses/MIT MIT
 */
class EditWalkerTest extends TestCase {

	public function test_start_el() {

		$field_1 = \Mockery::mock( EditField::class );
		$field_1->shouldReceive( 'field_markup' )->andReturn( '<input type="text" name="foo" />' );

		$field_2 = \Mockery::mock( EditField::class );
		$field_2->shouldReceive( 'field_markup' )->andReturn( '<input type="text" name="bar" />' );

		Filters\expectApplied( FILTER_FIELDS )->once()->andReturn( [ $field_1, $field_2 ] );

		$walker = new EditWalker();
		$output = '';
		$walker->start_el( $output, (object) [ 'ID' => 123 ], 0, [], 123 );

		static::assertContains( '<input type="text" name="foo"', $output );
		static::assertContains( '<input type="text" name="bar"', $output );
	}

}