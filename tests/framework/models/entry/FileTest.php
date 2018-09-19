<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use artichoke\framework\abstracts\MongodbBase;
use artichoke\framework\models\entry\File;

class FileTest extends MongodbTestCase
{
    const TESTARRAY_MINI = [
        'album_id' => 1,
        'device_id' => 3,
        'contentType' => 'application/octet-stream',
        'meta' => ['hoge' => 'fuga'],
        'thumbnailB64' => 'QUJDREVGRw==',
    ];
    const TESTARRAY_KILO = [
        'album_id' => 2,
        'device_id' => 4,
        'contentType' => 'audio/midi',
        'meta' => ['foo' => 'baz'],
        'lock' => true,
    ];
    const MINI_BYTES = 128;
    const KILO_BYTES = 1024;
    const MEGA_BYTES = 1048576; # 1024^2
    private $testBinary_mini;
    private $testBinary_kilo;
    private $testBinary_mega;
    private $testBinaryPath_mini;
    private $testBinaryPath_kilo;
    private $testBinaryPath_mega;
    private $testFileObjectId_mini;
    private $testFileObjectId_kilo;
    private $testFileObjectId_mega;
    private $testFileInstance_mini;
    private $testFileInstance_kilo;
    private $testFileInstance_mega;

    public function __construct()
    {
        parent::__construct();
        $this->testBinary_mini = $this->getRandomBinary(self::MINI_BYTES);
        $this->testBinary_kilo = $this->getRandomBinary(self::KILO_BYTES);
        $this->testBinary_mega = $this->getRandomBinary(self::MEGA_BYTES);
    }

    public function setUp()
    {
        MongodbBase::setDatabase($this->getTestDatabase()); # set mongodb test database

        $this->testBinaryPath_mini = $this->getTmpfilePathFromBinary($this->testBinary_mini);
        $this->testBinaryPath_kilo = $this->getTmpfilePathFromBinary($this->testBinary_kilo);
        $this->testBinaryPath_mega = $this->getTmpfilePathFromBinary($this->testBinary_mega);

        $this->testFileObjectId_mini = $this->createTestFile($this->testBinaryPath_mini, 'Test File (mini)', self::TESTARRAY_MINI);
        $this->testFileObjectId_kilo = $this->createTestFile($this->testBinaryPath_kilo, 'Test File (kilo)', self::TESTARRAY_KILO);
        $this->testFileObjectId_mega = $this->createTestFile($this->testBinaryPath_mega, 'Test File (mega)');

        $this->testFileInstance_mini = new File($this->testFileObjectId_mini);
        $this->testFileInstance_kilo = new File($this->testFileObjectId_kilo);
        $this->testFileInstance_mega = new File($this->testFileObjectId_mega);
    }

    public function test_getContentType()
    {
        $this->assertEquals(self::TESTARRAY_MINI['contentType'], $this->testFileInstance_mini->getContentType());
        $this->assertEquals(self::TESTARRAY_KILO['contentType'], $this->testFileInstance_kilo->getContentType());
        $this->assertEmpty($this->testFileInstance_mega->getContentType());
    }

    public function test_getFilename()
    {
        $this->assertEquals('Test File (mini)', $this->testFileInstance_mini->getFilename());
        $this->assertEquals('Test File (kilo)', $this->testFileInstance_kilo->getFilename());
        $this->assertEquals('Test File (mega)', $this->testFileInstance_mega->getFilename());
    }

    public function test_getMetadata()
    {
        $this->assertEquals(self::TESTARRAY_MINI['meta'], $this->testFileInstance_mini->getMetadata());
        $this->assertEquals(self::TESTARRAY_KILO['meta'], $this->testFileInstance_kilo->getMetadata());
        $this->assertEmpty($this->testFileInstance_mega->getMetadata());
    }

    public function test_getHash()
    {
        $this->assertEquals(md5($this->testBinary_mini), $this->testFileInstance_mini->getHash());
        $this->assertEquals(md5($this->testBinary_kilo), $this->testFileInstance_kilo->getHash());
        $this->assertEquals(md5($this->testBinary_mega), $this->testFileInstance_mega->getHash());
    }

    public function test_getBytesString()
    {
        $this->assertEquals(self::MINI_BYTES.' Bytes', $this->testFileInstance_mini->getBytesString());
        $this->assertEquals(round(self::KILO_BYTES / pow(1024, 1), 2).' KiB', $this->testFileInstance_kilo->getBytesString());
        $this->assertEquals(round(self::MEGA_BYTES / pow(1024, 2), 2).' MiB', $this->testFileInstance_mega->getBytesString());
        $this->assertEquals(round(self::MEGA_BYTES / pow(1024, 3), 2).' GiB', $this->testFileInstance_mega->getBytesString(File::BINARY_PREFIX_GIGA));
        // for overflow
        $this->assertEquals((float)self::MINI_BYTES, $this->testFileInstance_mini->getBytes(-20));
    }

    public function test_getThumbnail()
    {
        $this->assertEquals(self::TESTARRAY_MINI['thumbnailB64'], $this->testFileInstance_mini->getThumbnail(true));
        $this->assertEquals('ABCDEFG', $this->testFileInstance_mini->getThumbnail(false));
        $this->assertNotEmpty($this->testFileInstance_kilo->getThumbnail());
        $this->assertNotEmpty($this->testFileInstance_mega->getThumbnail(false));
    }

    public function test_getBinary()
    {
        $this->assertEquals($this->testBinary_mini, $this->testFileInstance_mini->getBinary());
        $this->assertEquals($this->testBinary_kilo, $this->testFileInstance_kilo->getBinary());
        $this->assertEquals($this->testBinary_mega, $this->testFileInstance_mega->getBinary());
    }

    public function test_getBinaryStream()
    {
        $this->assertEquals($this->testBinary_mini, stream_get_contents($this->testFileInstance_mini->getBinaryStream()));
        $this->assertEquals($this->testBinary_kilo, stream_get_contents($this->testFileInstance_kilo->getBinaryStream()));
        $this->assertEquals($this->testBinary_mega, stream_get_contents($this->testFileInstance_mega->getBinaryStream()));
    }
}
