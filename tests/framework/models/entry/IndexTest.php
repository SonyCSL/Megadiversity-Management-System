<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use artichoke\framework\abstracts\MongodbBase;
use artichoke\framework\models\entry\Index;
use artichoke\framework\models\entry\Data;

class IndexTest extends MongodbTestCase
{
    const TESTARRAY_FILE = [
        'album_id' => 1,
        'device_id' => 3,
        'meta' => ['hoge' => 'fuga'],
        'thumbnailB64' => 'QUJDREVGRw==',
    ];
    const TESTARRAY_DATASTRING = [
        'album_id' => 2,
        'device_id' => 4,
        'data' => [
            'trap' => [
                'value' => true,
            ],
        ],
        'lock' => true,
    ];
    const FILE_BYTES = 128;
    const FILE_CONTENT_TYPE = 'application/octet-stream';
    const FILE_NAME = 'IndexTestFile';
    private $testFileBinary;
    private $testFilePath;
    private $testFileObjectId;
    private $testDatastringObjectId;

    public function setUp()
    {
        MongodbBase::setDatabase($this->getTestDatabase()); # set mongodb test database

        // sample file
        $testArrayFile = self::TESTARRAY_FILE + [
            'contentType' => self::FILE_CONTENT_TYPE,
            'uploadDate' => new \MongoDB\BSON\UTCDateTime(),
        ];
        $this->testFileBinary = $this->getRandomBinary(self::FILE_BYTES);
        $this->testFilePath = $this->getTmpfilePathFromBinary($this->testFileBinary);
        $this->testFileObjectId = $this->createTestFile($this->testFilePath, self::FILE_NAME, $testArrayFile);

        // sample datastring
        $testArrayDatastring = self::TESTARRAY_DATASTRING + [
            'uploadDate' => new \MongoDB\BSON\UTCDateTime(),
        ];
        $this->testDatastringObjectId = $this->createTestDatastring($testArrayDatastring);
    }

    public function test_sample()
    {
        // index instances
        $fIndex = new Index(Index::FILES);
        $dIndex = new Index(Index::DATASTRINGS);

        // 4 pattern samples
        $exist_file = $fIndex->sample(1);
        $noany_file = $fIndex->sample(2);
        $exist_datastring = $dIndex->sample(2);
        $noany_datastring = $dIndex->sample(1);

        // try
        $this->assertEquals([
            'count' => 1,
            'countString' => '1 file',
            '_id' => $this->testFileObjectId,
        ], $exist_file);
        $this->assertEquals([
            'count' => 0,
            'countString' => '0 files',
            '_id' => null,
        ], $noany_file);
        $this->assertEquals([
            'count' => 1,
            'countString' => '1 string',
            '_id' => $this->testDatastringObjectId,
        ], $exist_datastring);
        $this->assertEquals([
            'count' => 0,
            'countString' => '0 strings',
            '_id' => null,
        ], $noany_datastring);
    }

    public function test_listup()
    {
        // index instances
        $fIndex = new Index(Index::FILES);
        $dIndex = new Index(Index::DATASTRINGS);

        // listup file
        foreach ($fIndex->listup(1) as $file) {
            $this->assertNotEmpty($file['_id']);
            $this->assertEquals(8, strlen($file['_id_short']));
            $this->assertInstanceOf(\DateTime::class, $file['upload_datetime']);
            $this->assertInstanceOf(Data::class, $file['data']);
            $this->assertEquals(self::FILE_NAME, $file['name']);
            $this->assertEquals(self::FILE_CONTENT_TYPE, $file['content_type']);
        }

        // listup datastring
        foreach ($dIndex->listup(2) as $datastring) {
            $this->assertNotEmpty($datastring['_id']);
            $this->assertEquals(8, strlen($datastring['_id_short']));
            $this->assertInstanceOf(\DateTime::class, $datastring['upload_datetime']);
            $this->assertInstanceOf(Data::class, $datastring['data']);
        }
    }
}
