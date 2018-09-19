<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use artichoke\framework\abstracts\MariadbBase;
use artichoke\framework\models\client\Device;

class DeviceTest extends MariadbTestCase
{
    const TEST_USER_ADMIN = ['1', 'TESTUSER-ADMIN', 'DUMMY_PASSWD', 'test.admin@localhost', '1', '1', ''];
    const TEST_USER_GENERAL = ['2', 'TESTUSER-GENERAL', 'DUMMY_PASSWD', 'test.general@localhost', '2', '1', ''];
    const TEST_ALBUM_ADMIN = ['1', '2018-01-01 00:00:00', '2018-06-11 23:54:12', '1', 'MY-ALBUM-TITLE-1', 'This is one', '7', '5', '4'];
    const TEST_ALBUM_GENERAL = ['2', '2018-01-02 00:00:00', '2018-06-12 15:11:53', '2', 'MY-ALBUM-TITLE-2', 'This is two', '7', '4', '4'];
    const TEST_DEVICE_1 = ['1', '1', 'DEVICE-ADMIN-1', '9', '135.0143 34.9766', '1', '50', '10', 'MY HOME', '', '0'];
    const TEST_DEVICE_2 = ['2', '1', 'DEVICE-ADMIN-2', '0', '1.4182 41.7165', '1', '600', '1000', 'EUROPE-CENTRE', '', '1'];
    const TEST_DEVICE_3 = ['3', '2', 'DEVICE-GENERAL-1', '9', '0 0', '0', '0', '1600', 'Mobile', 'wearable sensor built-in', '0'];
    const TEST_DEVICE_1_QUERY = "INSERT INTO device VALUES('1', '1', 'DEVICE-ADMIN-1', '9', GeomFromText('POINT(135.0143 34.9766)'), '1', '50', '10', 'MY HOME', '', '0')";
    const TEST_DEVICE_2_QUERY = "INSERT INTO device VALUES('2', '1', 'DEVICE-ADMIN-2', '0', GeomFromText('POINT(1.4182 41.7165)'), '1', '600', '1000', 'EUROPE-CENTRE', '', '1')";
    const TEST_DEVICE_3_QUERY = "INSERT INTO device VALUES('3', '2', 'DEVICE-GENERAL-1', '9', GeomFromText('POINT(0 0)'), '0', '0', '1600', 'Mobile', 'wearable sensor built-in', '0')";
    const TEST_UPLOADING_MAP = [
        ['DUMMY_ACCESSKEY', '1', '1', 'Device-1 to Album-Admin'],
        ['DUMMY_ACCESSKEY', '2', '2', 'Device-2 to Album-General'],
        ['DUMMY_ACCESSKEY', '1', '2', 'Device-1 to Album-General'],
    ];
    private $testDevice0;
    private $testDevice1;
    private $testDevice2;
    private $testDevice3;
    private $testDevice4;
    private $testAccesskeys;

    public function setUp()
    {
        MariadbBase::setConnector($this->getTestConnector());
        $this->tableCleanUp('device');
        $this->tableCleanUp('user');
        $this->tableCleanUp('upload_identifier');

        // create test users, devices, acckeys
        $this->dbTestInsert('user', self::TEST_USER_ADMIN);
        $this->dbTestInsert('user', self::TEST_USER_GENERAL);
        $this->dbTestQuery(self::TEST_DEVICE_1_QUERY);
        $this->dbTestQuery(self::TEST_DEVICE_2_QUERY);
        $this->dbTestQuery(self::TEST_DEVICE_3_QUERY);
        foreach (self::TEST_UPLOADING_MAP as $keymap) {
            $keymap[0] = hash('sha256', uniqid()); # Random accesskey
            $this->testAccesskeys[] = $keymap[0];
            $this->dbTestInsert('upload_identifier', $keymap);
        }

        $this->testDevice0 = new Device(0);
        $this->testDevice1 = new Device($this->testAccesskeys[0]);
        $this->testDevice2 = new Device($this->testAccesskeys[1]);
        $this->testDevice3 = new Device(3);
        $this->testDevice4 = new Device(4);
        $this->testDevice1p = new Device($this->testAccesskeys[2]);
    }

    public function test_construct_and_exists()
    {
        $this->assertFalse((new Device(0))->exists());
        $this->assertTrue((new Device($this->testAccesskeys[0]))->exists());
        $this->assertTrue((new Device(2))->exists());
        $this->assertFalse((new Device('12f8da06'))->exists());
        $this->assertFalse((new Device(true))->exists());
    }

    public function test_available()
    {
        $this->assertFalse($this->testDevice0->available());
        $this->assertTrue($this->testDevice1->available());
        $this->assertFalse($this->testDevice2->available());
        $this->assertTrue($this->testDevice3->available());
        $this->assertFalse($this->testDevice4->available());
    }

    public function test_getInfo()
    {
        $this->assertNull($this->testDevice0->getInfo('devicename'));
        $this->assertEquals(self::TEST_DEVICE_1[2], $this->testDevice1->getInfo('devicename'));
        $this->assertArraySubset([
            'devicename' => self::TEST_DEVICE_2[2],
            'place' => self::TEST_DEVICE_2[8],
        ], $this->testDevice2->getInfo());
        $this->assertNull($this->testDevice3->getInfo('device_option'));
        $this->assertNull($this->testDevice4->getInfo());
    }

    public function test_getId()
    {
        $this->assertEmpty($this->testDevice0->getId());
        $this->assertEmpty((string)$this->testDevice0);
        $this->assertEquals((int)self::TEST_DEVICE_1[0], $this->testDevice1->getId());
        $this->assertEquals((string)self::TEST_DEVICE_1[0], (string)$this->testDevice1);
        $this->assertEquals((int)self::TEST_DEVICE_2[0], $this->testDevice2->getId());
        $this->assertEquals((string)self::TEST_DEVICE_2[0], (string)$this->testDevice2);
        $this->assertEquals((int)self::TEST_DEVICE_3[0], $this->testDevice3->getId());
        $this->assertEquals((string)self::TEST_DEVICE_3[0], (string)$this->testDevice3);
        $this->assertEmpty($this->testDevice4->getId());
        $this->assertEmpty((string)$this->testDevice4);
    }

    public function test_getName()
    {
        $this->assertEmpty($this->testDevice0->getName());
        $this->assertEquals(self::TEST_DEVICE_1[2], $this->testDevice1->getName());
        $this->assertEquals(self::TEST_DEVICE_2[2], $this->testDevice2->getName());
        $this->assertEquals(self::TEST_DEVICE_3[2], $this->testDevice3->getName());
        $this->assertEmpty($this->testDevice4->getName());
    }

    public function test_getOwnerId()
    {
        $this->assertEmpty($this->testDevice0->getOwnerId());
        $this->assertEquals((int)self::TEST_DEVICE_1[1], $this->testDevice1->getOwnerId());
        $this->assertEquals((int)self::TEST_DEVICE_2[1], $this->testDevice2->getOwnerId());
        $this->assertEquals((int)self::TEST_DEVICE_3[1], $this->testDevice3->getOwnerId());
        $this->assertEmpty($this->testDevice4->getOwnerId());
    }

    public function test_getAlbumId()
    {
        $this->assertEmpty($this->testDevice0->getAlbumId());
        $this->assertEquals(1, $this->testDevice1->getAlbumId());
        $this->assertEquals(2, $this->testDevice1p->getAlbumId());
        $this->assertEquals(2, $this->testDevice2->getAlbumId());
        $this->assertEmpty($this->testDevice3->getAlbumId());
        $this->assertEmpty($this->testDevice4->getAlbumId());
    }

    public function test_getGeoJsonArray()
    {
        $this->assertEmpty($this->testDevice0->getGeoJsonArray());
        $this->assertEquals([
            'type' => 'Point',
            'coordinates' => [(float)(explode(' ', self::TEST_DEVICE_1[4]))[0], (float)(explode(' ', self::TEST_DEVICE_1[4]))[1]],
            'altitude' => (float)self::TEST_DEVICE_1[6],
            'groundHeight' => (int)self::TEST_DEVICE_1[7],
            'place' => self::TEST_DEVICE_1[8],
        ], $this->testDevice1->getGeoJsonArray());
        $this->assertEquals([
            'type' => 'Point',
            'coordinates' => [(float)(explode(' ', self::TEST_DEVICE_2[4]))[0], (float)(explode(' ', self::TEST_DEVICE_2[4]))[1]],
            'altitude' => (float)self::TEST_DEVICE_2[6],
            'groundHeight' => (int)self::TEST_DEVICE_2[7],
            'place' => self::TEST_DEVICE_2[8],
        ], $this->testDevice2->getGeoJsonArray());
        $this->assertEquals([
            'type' => 'Point',
            'coordinates' => [(float)(explode(' ', self::TEST_DEVICE_3[4]))[0], (float)(explode(' ', self::TEST_DEVICE_3[4]))[1]],
            'altitude' => (float)self::TEST_DEVICE_3[6],
            'groundHeight' => (int)self::TEST_DEVICE_3[7],
            'place' => self::TEST_DEVICE_3[8],
        ], $this->testDevice3->getGeoJsonArray());
        $this->assertEmpty($this->testDevice4->getGeoJsonArray());
    }
}
