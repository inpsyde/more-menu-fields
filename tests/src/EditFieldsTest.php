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
use Inpsyde\MoreMenuFields\EditFields;
use Inpsyde\MoreMenuFields\EditFieldValueFactory;
use Inpsyde\MoreMenuFields\SanitizedEditField;
use Brain\Monkey\Filters;

use const Inpsyde\MoreMenuFields\FILTER_FIELDS;

/**
 * @package more-menu-fields
 * @license http://opensource.org/licenses/MIT MIT
 */
class EditFieldsTest extends TestCase {

	public function test_all_fields() {

		$field_1 = \Mockery::mock( EditField::class );
		$field_2 = \Mockery::mock( SanitizedEditField::class );
		$field_3 = new \stdClass();

		Filters\expectApplied( FILTER_FIELDS )
			->once()
			->with( \Mockery::type( 'array'), \Mockery::type( EditFieldValueFactory::class ) )
			->andReturn( [ $field_1, $field_2, $field_3 ] );

		$fields = new EditFields();

		static::assertSame( [ $field_1, $field_2 ], $fields->all_fields( 123 ) );
	}

}