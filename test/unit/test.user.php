<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
// 
// Copyright (c) 2008-2010 Ted Kulp
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

//require_once(dirname(dirname(__FILE__)) . '/silk.api.php');

use \silk\test\TestCase;
use \silk\database\Database;
use \silk\performance\Cache;
use \cmsms\auth\User;

class UserTest extends TestCase
{
	var $_fixtures = array('user');

	public function testAllUsersCanBeFound()
	{
		$user = new User();
		$users = $user->all()->order(array('id' => 'ASC'))->execute();
		$this->assertEquals(2, count($users));
		$this->assertEquals('Test', $users[0]->first_name);
		$this->assertEquals('User', $users[0]->last_name);
	}

	public function testUserPasswordCheck()
	{
		$user = new User();
		$user = $user->first()->execute();
		$this->assertNotNull($user);
		$this->assertFalse($user->check_password('blahblah'));
		$this->assertTrue($user->check_password('test'));
	}

	public function testUserPasswordSet()
	{
		$user = new User();
		$user = $user->first()->execute();
		$user->set_password('thing');
		$this->assertTrue($user->save());
		$user = $user->first()->execute();
		$this->assertFalse($user->check_password('test'));
		$this->assertTrue($user->check_password('thing'));
	}
}

# vim:ts=4 sw=4 noet
