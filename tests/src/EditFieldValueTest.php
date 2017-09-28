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

use Inpsyde\MoreMenuFields\EditFieldValue;
use Brain\Monkey\Functions;

/**
 * @package more-menu-fields
 * @license http://opensource.org/licenses/MIT MIT
 */
class EditFieldValueTest extends TestCase {

	public function test_is_valid_is_false_when_bad_id() {

		Functions\expect( 'is_nav_menu_item' )->with( 123 )->andReturn( FALSE );

		$value = new EditFieldValue( 'x', 123 );

		static::assertFalse( $value->is_valid() );
	}

	public function test_form_field_name() {

		Functions\expect( 'is_nav_menu_item' )->with( 123 )->andReturn( TRUE );

		$value = new EditFieldValue( 'a_name', 123 );

		static::assertRegExp( '/^_.+?a_name\[123\]$/', $value->form_field_name() );
	}

	public function test_form_field_id() {

		Functions\expect( 'is_nav_menu_item' )->with( 456 )->andReturn( TRUE );

		$value = new EditFieldValue( 'a_name', 456 );

		static::assertRegExp( '/^_.+?a_name\-456$/', $value->form_field_id() );
	}

	public function test_form_field_class() {

		Functions\expect( 'is_nav_menu_item' )->with( 789 )->andReturn( TRUE );
		Functions\when( 'sanitize_html_class' )->returnArg();

		$value = new EditFieldValue( 'a_name', 789 );

		static::assertContains( 'field-a_name', $value->form_field_class() );
	}

	public function test_value() {

		Functions\expect( 'is_nav_menu_item' )->with( 101 )->andReturn( TRUE );
		Functions\expect( 'get_post_meta' )
			->with( 101, \Mockery::type( 'string' ), TRUE )
			->andReturnUsing( function ( int $id, string $key, bool $true ) {
				static::assertRegExp( '/^_.+?a_name$/', $key );

				return '42';
			} );

		$value = new EditFieldValue( 'a_name', 101, 'intval' );

		static::assertSame( 42, $value->value() );
	}

	public function test_save_do_nothing_if_not_saving() {

		Functions\expect( 'is_nav_menu_item' )->with( 112 )->andReturn( TRUE );
		$value = new EditFieldValue( 'a_name', 112, 'intval' );

		static::assertFalse( $value->save() );
	}

	public function test_save_do_nothing_current_value_equals_new_value() {

		Functions\expect( 'is_nav_menu_item' )->with( 131 )->andReturn( TRUE );

		Functions\expect( 'filter_input' )->andReturn( [ 131 => '123456789' ] );

		Functions\expect( 'get_post_meta' )->andReturn( 123456789 );

		Functions\expect( 'doing_action' )->with( 'wp_update_nav_menu_item' )->andReturn( TRUE );

		$value = new EditFieldValue( 'a_name', 131, 'intval' );

		static::assertTrue( $value->save() );
	}

	public function test_save_updates_value() {

		Functions\expect( 'is_nav_menu_item' )->with( 415 )->andReturn( TRUE );

		Functions\expect( 'filter_input' )->andReturn( [ 415 => '123456789' ] );

		Functions\expect( 'get_post_meta' )->andReturn( FALSE );

		Functions\expect( 'doing_action' )->with( 'wp_update_nav_menu_item' )->andReturn( TRUE );

		Functions\expect( 'update_post_meta' )
			->with( 415, \Mockery::type( 'string' ), 123456789  )
			->andReturn( TRUE );

		$value = new EditFieldValue( 'a_name', 415, 'intval' );

		static::assertTrue( $value->save() );
	}

	public function test_save_deletes_value() {

		Functions\expect( 'is_nav_menu_item' )->with( 161 )->andReturn( TRUE );

		Functions\expect( 'filter_input' )->andReturn( [] );

		Functions\expect( 'get_post_meta' )->andReturn( 123456789 );

		Functions\expect( 'doing_action' )->with( 'wp_update_nav_menu_item' )->andReturn( TRUE );

		Functions\expect( 'delete_post_meta' )
			->with( 161, \Mockery::type( 'string' ) )
			->andReturn( TRUE );

		$value = new EditFieldValue( 'a_name', 161, 'intval' );

		static::assertTrue( $value->save() );
	}

}