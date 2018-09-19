<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use artichoke\framework\abstracts\MariadbBase;
use artichoke\framework\models\client\User;

class UserTest extends MariadbTestCase
{
    const TEST_USER_ADMIN = ['1', 'TESTUSER-ADMIN', 'DUMMY_PASSWD', 'test.admin@localhost', '1', '1', ''];
    const TEST_USER_GENERAL = ['2', 'TESTUSER-GENERAL', 'DUMMY_PASSWD', 'test.general@localhost', '2', '1', ''];
    const TEST_USER_ADVISER = ['3', 'TESTUSER-ADVISER', 'DUMMY_PASSWD', 'test.adviser@localhost', '3', '1', ''];
    const TEST_USER_VIEWER = ['4', 'TESTUSER-VIEWER', 'DUMMY_PASSWD', 'test.viewer@localhost', '4', '1', ''];
    const TEST_USER_UPLOADER = ['5', 'TESTUSER-UPLOADER', 'DUMMY_PASSWD', 'test.uploader@localhost', '5', '1', ''];
    private $testUser0;
    private $testUser1;
    private $testUser2;
    private $testUser3;
    private $testUser4;
    private $testUser5;

    public function setUp()
    {
        MariadbBase::setConnector($this->getTestConnector());
        $this->tableCleanUp('user');

        // create test users & albums
        $this->dbTestInsert('user', self::TEST_USER_ADMIN);
        $this->dbTestInsert('user', self::TEST_USER_GENERAL);
        $this->dbTestInsert('user', self::TEST_USER_ADVISER);
        $this->dbTestInsert('user', self::TEST_USER_VIEWER);
        $this->dbTestInsert('user', self::TEST_USER_UPLOADER);

        $this->testUser0 = new User(0);
        $this->testUser1 = new User(1);
        $this->testUser2 = new User(2);
        $this->testUser3 = new User(3);
        $this->testUser4 = new User(4);
        $this->testUser5 = new User(5);
    }

    public function test_construct_and_exists()
    {
        $this->assertFalse((new User(0))->exists());
        $this->assertTrue((new User('TESTUSER-ADMIN'))->exists());
        $this->assertTrue((new User('test.general@localhost'))->exists());
        $this->assertTrue((new User(3))->exists());
        $this->assertFalse((new User(true))->exists());
    }

    public function test_loginAuth()
    {
        // re-create
        $this->tableCleanUp('user');
        #1
        $testAdmin = self::TEST_USER_ADMIN;
        $testAdmin[2] = hash('sha256', 'tHeW0rld');
        #2
        $testGeneral = self::TEST_USER_GENERAL;
        $testGeneral[2] = password_hash('Crazy-d1amond!', PASSWORD_DEFAULT);
        #3
        $testUnknown = self::TEST_USER_ADVISER;

        $this->dbTestInsert('user', $testAdmin);
        $this->dbTestInsert('user', $testGeneral);
        $this->dbTestInsert('user', $testUnknown);

        // try
        $this->assertTrue((new User(1))->loginAuth('tHeW0rld'));
        $this->assertTrue((new User(2))->loginAuth('Crazy-d1amond!'));
        $this->assertFalse((new User(3))->loginAuth('letmein'));
        $this->assertFalse((new User(3))->loginAuth(''));
        $this->assertFalse((new User(0))->loginAuth('0000'));
    }

    public function test_isAdmin()
    {
        $this->assertFalse($this->testUser0->isAdmin());
        $this->assertTrue($this->testUser1->isAdmin());
        $this->assertFalse($this->testUser2->isAdmin());
        $this->assertFalse($this->testUser3->isAdmin());
        $this->assertFalse($this->testUser4->isAdmin());
        $this->assertFalse($this->testUser5->isAdmin());
    }

    public function test_getInfo()
    {
        $this->assertNull($this->testUser0->getInfo('username'));
        $this->assertEquals(self::TEST_USER_ADMIN[1], $this->testUser1->getInfo('username'));
        $this->assertArraySubset([
            'username' => self::TEST_USER_GENERAL[1],
            'email' => self::TEST_USER_GENERAL[3],
        ], $this->testUser2->getInfo());
        $this->assertNull($this->testUser3->getInfo('user_option'));
        $this->assertNull((new User(99))->getInfo());
    }

    public function test_getId()
    {
        $this->assertEmpty($this->testUser0->getId());
        $this->assertEquals((int)self::TEST_USER_ADMIN[0], $this->testUser1->getId());
        $this->assertEquals((int)self::TEST_USER_GENERAL[0], $this->testUser2->getId());
        $this->assertEquals((int)self::TEST_USER_ADVISER[0], $this->testUser3->getId());
        $this->assertEquals((int)self::TEST_USER_VIEWER[0], $this->testUser4->getId());
        $this->assertEquals((int)self::TEST_USER_UPLOADER[0], $this->testUser5->getId());
    }

    public function test_getName()
    {
        $this->assertEmpty($this->testUser0->getName());
        $this->assertEquals(self::TEST_USER_ADMIN[1], $this->testUser1->getName());
        $this->assertEquals(self::TEST_USER_GENERAL[1], $this->testUser2->getName());
        $this->assertEquals(self::TEST_USER_ADVISER[1], $this->testUser3->getName());
        $this->assertEquals(self::TEST_USER_VIEWER[1], $this->testUser4->getName());
        $this->assertEquals(self::TEST_USER_UPLOADER[1], $this->testUser5->getName());
    }

    public function test_getGroupName()
    {
        $this->assertEmpty($this->testUser0->getGroupName());
        $this->assertEquals('Administrator', $this->testUser1->getGroupName());
        $this->assertEquals('General user', $this->testUser2->getGroupName());
        $this->assertEquals('Adviser', $this->testUser3->getGroupName());
        $this->assertEquals('Viewer', $this->testUser4->getGroupName());
        $this->assertEquals('Uploader', $this->testUser5->getGroupName());
    }

    public function test_editAll()
    {
        $this->assertFalse($this->testUser0->editAll());
        $this->assertTrue($this->testUser1->editAll());
        $this->assertFalse($this->testUser2->editAll());

        $this->assertTrue($this->testUser3->editAll());
        $this->assertFalse($this->testUser4->editAll());
        $this->assertFalse($this->testUser5->editAll());
    }

    public function test_viewAll()
    {
        $this->assertFalse($this->testUser0->viewAll());
        $this->assertTrue($this->testUser1->viewAll());
        $this->assertFalse($this->testUser2->viewAll());

        $this->assertFalse($this->testUser3->viewAll());
        $this->assertTrue($this->testUser4->viewAll());
        $this->assertFalse($this->testUser5->viewAll());
    }

    public function test_uploadAll()
    {
        $this->assertFalse($this->testUser0->uploadAll());
        $this->assertTrue($this->testUser1->uploadAll());
        $this->assertFalse($this->testUser2->uploadAll());

        $this->assertFalse($this->testUser3->uploadAll());
        $this->assertFalse($this->testUser4->uploadAll());
        $this->assertTrue($this->testUser5->uploadAll());
    }
}
