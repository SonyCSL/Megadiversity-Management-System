<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use artichoke\framework\abstracts\MariadbBase;
use artichoke\framework\models\client\Bookshelf;
use artichoke\framework\models\client\User;

class BookshelfTest extends MariadbTestCase
{
    const TEST_USER_ADMIN = ['1', 'TESTUSER-ADMIN', 'DUMMY_PASSWD', 'test.admin@localhost', '1', '1', ''];
    const TEST_USER_GENERAL = ['2', 'TESTUSER-GENERAL', 'DUMMY_PASSWD', 'test.general@localhost', '2', '1', ''];
    const TEST_USER_ADVISER = ['3', 'TESTUSER-ADVISER', 'DUMMY_PASSWD', 'test.adviser@localhost', '3', '1', ''];
    const TEST_USER_VIEWER = ['4', 'TESTUSER-VIEWER', 'DUMMY_PASSWD', 'test.viewer@localhost', '4', '1', ''];
    const TEST_USER_UPLOADER = ['5', 'TESTUSER-UPLOADER', 'DUMMY_PASSWD', 'test.uploader@localhost', '5', '1', ''];
    const TEST_ALBUM_1 = ['1', '2018-01-01 00:00:00', '2018-06-11 23:54:12', '1', 'MY-ALBUM-TITLE-1', 'This is one', '7', '5', '4'];
    const TEST_ALBUM_2 = ['2', '2018-01-02 00:00:00', '2018-06-12 15:11:53', '2', 'MY-ALBUM-TITLE-2', 'This is two', '7', '1', '0'];
    const TEST_ALBUM_3 = ['3', '2018-01-03 00:00:00', '2018-06-13 13:10:30', '5', 'MY-ALBUM-TITLE-3', 'This is three', '7', '5', '0'];
    const TEST_MEMBERS = [
        [1, 2],
        [1, 3],
        [1, 4],
        [2, 4],
        [2, 5],
    ];
    private $testUser1;
    private $testUser2;
    private $testUser3;
    private $testUser4;
    private $testUser5;

    public function setUp()
    {
        MariadbBase::setConnector($this->getTestConnector());
        $this->tableCleanUp('album_shared_members');
        $this->tableCleanUp('album');
        $this->tableCleanUp('user');

        // create test users & albums
        $this->dbTestInsert('user', self::TEST_USER_ADMIN);
        $this->dbTestInsert('user', self::TEST_USER_GENERAL);
        $this->dbTestInsert('user', self::TEST_USER_ADVISER);
        $this->dbTestInsert('user', self::TEST_USER_VIEWER);
        $this->dbTestInsert('user', self::TEST_USER_UPLOADER);
        $this->dbTestInsert('album', self::TEST_ALBUM_1);
        $this->dbTestInsert('album', self::TEST_ALBUM_2);
        $this->dbTestInsert('album', self::TEST_ALBUM_3);
        foreach (self::TEST_MEMBERS as $album_member) {
            // insert
            $this->dbTestInsert('album_shared_members', $album_member);
        }

        $this->testUser1 = new User(1);
        $this->testUser2 = new User(2);
        $this->testUser3 = new User(3);
        $this->testUser4 = new User(4);
        $this->testUser5 = new User(5);
    }

    public function test_count()
    {
        $this->assertEquals(3, (new Bookshelf($this->testUser1))->count());
        $this->assertEquals(2, (new Bookshelf($this->testUser2))->count());
        $this->assertEquals(1, (new Bookshelf($this->testUser3))->count());
        $this->assertEquals(3, (new Bookshelf($this->testUser4))->count());
        $this->assertEquals(2, (new Bookshelf($this->testUser5))->count());
    }

    public function test_listup()
    {
        $user1_listup = iterator_to_array((new Bookshelf($this->testUser1))->listup());
        $this->assertArraySubset([
            '_id' => (int)self::TEST_ALBUM_1[0],
            'title' => self::TEST_ALBUM_1[4],
            'username' => (new User((int)self::TEST_ALBUM_1[3]))->getName(),
            'modified_timestamp' => self::TEST_ALBUM_1[2],
        ], $user1_listup[2]);
        $this->assertArraySubset([
            '_id' => (int)self::TEST_ALBUM_2[0],
            'title' => self::TEST_ALBUM_2[4],
            'username' => (new User((int)self::TEST_ALBUM_2[3]))->getName(),
            'modified_timestamp' => self::TEST_ALBUM_2[2],
        ], $user1_listup[1]);
        $this->assertArraySubset([
            '_id' => (int)self::TEST_ALBUM_3[0],
            'title' => self::TEST_ALBUM_3[4],
            'username' => (new User((int)self::TEST_ALBUM_3[3]))->getName(),
            'modified_timestamp' => self::TEST_ALBUM_3[2],
        ], $user1_listup[0]);

        $user2_listup = iterator_to_array((new Bookshelf($this->testUser2))->listup());
        $this->assertArraySubset([
            '_id' => (int)self::TEST_ALBUM_1[0],
            'title' => self::TEST_ALBUM_1[4],
            'username' => (new User((int)self::TEST_ALBUM_1[3]))->getName(),
            'modified_timestamp' => self::TEST_ALBUM_1[2],
        ], $user2_listup[1]);
        $this->assertArraySubset([
            '_id' => (int)self::TEST_ALBUM_2[0],
            'title' => self::TEST_ALBUM_2[4],
            'username' => (new User((int)self::TEST_ALBUM_2[3]))->getName(),
            'modified_timestamp' => self::TEST_ALBUM_2[2],
        ], $user2_listup[0]);

        $user3_listup = iterator_to_array((new Bookshelf($this->testUser3))->listup());
        $this->assertArraySubset([
            '_id' => (int)self::TEST_ALBUM_1[0],
            'title' => self::TEST_ALBUM_1[4],
            'username' => (new User((int)self::TEST_ALBUM_1[3]))->getName(),
            'modified_timestamp' => self::TEST_ALBUM_1[2],
        ], $user3_listup[0]);

        $user4_listup = iterator_to_array((new Bookshelf($this->testUser4))->listup());
        $this->assertArraySubset([
            '_id' => (int)self::TEST_ALBUM_1[0],
            'title' => self::TEST_ALBUM_1[4],
            'username' => (new User((int)self::TEST_ALBUM_1[3]))->getName(),
            'modified_timestamp' => self::TEST_ALBUM_1[2],
        ], $user4_listup[2]);
        $this->assertArraySubset([
            '_id' => (int)self::TEST_ALBUM_2[0],
            'title' => self::TEST_ALBUM_2[4],
            'username' => (new User((int)self::TEST_ALBUM_2[3]))->getName(),
            'modified_timestamp' => self::TEST_ALBUM_2[2],
        ], $user4_listup[1]);
        $this->assertArraySubset([
            '_id' => (int)self::TEST_ALBUM_3[0],
            'title' => self::TEST_ALBUM_3[4],
            'username' => (new User((int)self::TEST_ALBUM_3[3]))->getName(),
            'modified_timestamp' => self::TEST_ALBUM_3[2],
        ], $user4_listup[0]);

        $user5_listup = iterator_to_array((new Bookshelf($this->testUser5))->listup());
        $this->assertArraySubset([
            '_id' => (int)self::TEST_ALBUM_1[0],
            'title' => self::TEST_ALBUM_1[4],
            'username' => (new User((int)self::TEST_ALBUM_1[3]))->getName(),
            'modified_timestamp' => self::TEST_ALBUM_1[2],
        ], $user5_listup[1]);
        $this->assertArraySubset([
            '_id' => (int)self::TEST_ALBUM_3[0],
            'title' => self::TEST_ALBUM_3[4],
            'username' => (new User((int)self::TEST_ALBUM_3[3]))->getName(),
            'modified_timestamp' => self::TEST_ALBUM_3[2],
        ], $user5_listup[0]);
    }
}
