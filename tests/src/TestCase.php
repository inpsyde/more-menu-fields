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

use Brain\Monkey;

/**
 * @package more-menu-fields
 * @license http://opensource.org/licenses/MIT MIT
 */
class TestCase extends \PHPUnit\Framework\TestCase {

	protected function setUp() {

		parent::setUp();
		Monkey\setUp();

	}

	protected function tearDown() {

		Monkey\tearDown();
		parent::tearDown();
	}
}