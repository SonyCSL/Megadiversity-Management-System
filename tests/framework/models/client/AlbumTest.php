<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use artichoke\framework\abstracts\MariadbBase;
use artichoke\framework\models\client\Album;
use artichoke\framework\models\client\User;

class AlbumTest extends MariadbTestCase
{
    const TEST_USER_ADMIN = ['1', 'TESTUSER-ADMIN', 'DUMMY_PASSWD', 'test.admin@localhost', '1', '1', ''];
    const TEST_USER_GENERAL = ['2', 'TESTUSER-GENERAL', 'DUMMY_PASSWD', 'test.general@localhost', '2', '1', ''];
    const TEST_USER_ADVISER = ['3', 'TESTUSER-ADVISER', 'DUMMY_PASSWD', 'test.adviser@localhost', '3', '1', ''];
    const TEST_USER_VIEWER = ['4', 'TESTUSER-VIEWER', 'DUMMY_PASSWD', 'test.viewer@localhost', '4', '1', ''];
    const TEST_USER_UPLOADER = ['5', 'TESTUSER-UPLOADER', 'DUMMY_PASSWD', 'test.uploader@localhost', '5', '1', ''];
    const TEST_ALBUM_1 = ['1', '2018-01-01 00:00:00', '2018-06-11 23:54:12', '1', 'MY-ALBUM-TITLE-1', 'This is one', '7', '5', '4'];
    const TEST_ALBUM_2 = ['2', '2018-01-02 00:00:00', '2018-06-12 15:11:53', '2', 'MY-ALBUM-TITLE-2', 'This is two', '7', '4', '4'];
    const TEST_ALBUM_3 = ['3', '2018-01-03 00:00:00', '2018-06-13 13:10:30', '5', 'MY-ALBUM-TITLE-3', 'This is three', '7', '5', '0'];
    const TEST_MEMBERS = [
        [1, 2],
        [1, 3],
        [1, 4],
        [2, 4],
        [2, 5],
    ];
    private $testAlbum0;
    private $testAlbum1;
    private $testAlbum2;
    private $testAlbum3;
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

        $this->testAlbum0 = new Album(0);
        $this->testAlbum1 = new Album(1);
        $this->testAlbum2 = new Album(2);
        $this->testAlbum3 = new Album(3);
        $this->testUser1 = new User(1);
        $this->testUser2 = new User(2);
        $this->testUser3 = new User(3);
        $this->testUser4 = new User(4);
        $this->testUser5 = new User(5);
    }

    public function test_exists()
    {
        $this->assertFalse($this->testAlbum0->exists());
        $this->assertTrue($this->testAlbum1->exists());
        $this->assertTrue($this->testAlbum2->exists());
        $this->assertTrue($this->testAlbum3->exists());
        $this->assertFalse((new Album(4))->exists());
    }

    public function test_getInfo()
    {
        $this->assertNull($this->testAlbum0->getInfo('album_id'));
        $this->assertEquals(self::TEST_ALBUM_1[0], $this->testAlbum1->getInfo('album_id'));
        $this->assertArraySubset([
            'album_id' => self::TEST_ALBUM_2[0],
            'title' => self::TEST_ALBUM_2[4],
        ], $this->testAlbum2->getInfo());
        $this->assertNull($this->testAlbum3->getInfo('album_option'));
        $this->assertNull((new Album(4))->getInfo());
    }

    public function test_getOwner()
    {
        $this->assertFalse($this->testAlbum0->getOwner()->exists());
        $this->assertTrue($this->testAlbum1->getOwner()->exists());
        $this->assertTrue($this->testAlbum2->getOwner()->exists());
        $this->assertTrue($this->testAlbum3->getOwner()->exists());
    }

    public function test_getTitle()
    {
        $this->assertEmpty((new Album(0))->getTitle());
        $this->assertEquals(self::TEST_ALBUM_1[4], (new Album(1))->getTitle());
        $this->assertEquals(self::TEST_ALBUM_2[4], (new Album(2))->getTitle());
        $this->assertEquals(self::TEST_ALBUM_3[4], (new Album(3))->getTitle());
    }

    public function test_getDescription()
    {
        $this->assertEmpty($this->testAlbum0->getDescription());
        $this->assertEquals(self::TEST_ALBUM_1[5], $this->testAlbum1->getDescription());
        $this->assertEquals(self::TEST_ALBUM_2[5], $this->testAlbum2->getDescription());
        $this->assertEquals(self::TEST_ALBUM_3[5], $this->testAlbum3->getDescription());
    }

    public function test_viewable()
    {
        $this->assertTrue($this->testAlbum1->viewable($this->testUser1));
        $this->assertTrue($this->testAlbum1->viewable($this->testUser2));
        $this->assertTrue($this->testAlbum1->viewable($this->testUser3));
        $this->assertTrue($this->testAlbum1->viewable($this->testUser4));
        $this->assertTrue($this->testAlbum1->viewable($this->testUser5));
        $this->assertTrue($this->testAlbum2->viewable($this->testUser1));
        $this->assertTrue($this->testAlbum2->viewable($this->testUser2));
        $this->assertTrue($this->testAlbum2->viewable($this->testUser3));
        $this->assertTrue($this->testAlbum2->viewable($this->testUser4));
        $this->assertTrue($this->testAlbum2->viewable($this->testUser5));
        $this->assertTrue($this->testAlbum3->viewable($this->testUser1));
        $this->assertTrue($this->testAlbum3->viewable($this->testUser2));
        $this->assertTrue($this->testAlbum3->viewable($this->testUser3));
        $this->assertTrue($this->testAlbum3->viewable($this->testUser4));
        $this->assertTrue($this->testAlbum3->viewable($this->testUser5));
    }

    public function test_editable()
    {
        $this->assertTrue($this->testAlbum1->editable($this->testUser1));
        $this->assertFalse($this->testAlbum1->editable($this->testUser2));
        $this->assertTrue($this->testAlbum1->editable($this->testUser3));
        $this->assertFalse($this->testAlbum1->editable($this->testUser4));
        $this->assertFalse($this->testAlbum1->editable($this->testUser5));
        $this->assertTrue($this->testAlbum2->editable($this->testUser1));
        $this->assertTrue($this->testAlbum2->editable($this->testUser2));
        $this->assertTrue($this->testAlbum2->editable($this->testUser3));
        $this->assertFalse($this->testAlbum2->editable($this->testUser4));
        $this->assertFalse($this->testAlbum2->editable($this->testUser5));
        $this->assertTrue($this->testAlbum3->editable($this->testUser1));
        $this->assertFalse($this->testAlbum3->editable($this->testUser2));
        $this->assertTrue($this->testAlbum3->editable($this->testUser3));
        $this->assertFalse($this->testAlbum3->editable($this->testUser4));
        $this->assertTrue($this->testAlbum3->editable($this->testUser5));
    }

    public function test_uploadable()
    {
        $this->assertTrue($this->testAlbum1->uploadable($this->testUser1));
        $this->assertTrue($this->testAlbum1->uploadable($this->testUser2));
        $this->assertTrue($this->testAlbum1->uploadable($this->testUser3));
        $this->assertTrue($this->testAlbum1->uploadable($this->testUser4));
        $this->assertTrue($this->testAlbum1->uploadable($this->testUser5));
        $this->assertTrue($this->testAlbum2->uploadable($this->testUser1));
        $this->assertTrue($this->testAlbum2->uploadable($this->testUser2));
        $this->assertFalse($this->testAlbum2->uploadable($this->testUser3));
        $this->assertFalse($this->testAlbum2->uploadable($this->testUser4));
        $this->assertTrue($this->testAlbum2->uploadable($this->testUser5));
        $this->assertTrue($this->testAlbum3->uploadable($this->testUser1));
        $this->assertTrue($this->testAlbum3->uploadable($this->testUser2));
        $this->assertTrue($this->testAlbum3->uploadable($this->testUser3));
        $this->assertTrue($this->testAlbum3->uploadable($this->testUser4));
        $this->assertTrue($this->testAlbum3->uploadable($this->testUser5));
    }
}
