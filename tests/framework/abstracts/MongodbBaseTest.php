<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

require_once __DIR__.'/MongodbTestCase.php'; # alternative TestCase for MongoDB
use artichoke\framework\abstracts\MongodbBase;

class MongodbBaseTest extends MongodbTestCase
{
    private $stub;
    private $reflection;
    const TESTDATA_BINARY_TEXT = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, \nsed do eiusmod tempor incididunt ut labore et dolore magna aliqua.";
    const TESTDATA_INVALID_ID_ARRAY = [
        'hoge',
        '2124795',
        '56fad2c36118fd2e9820cfc1',
    ];

    public function setUp()
    {
        MongodbBase::setDatabase($this->getTestDatabase()); # set mongodb test database
        $this->stub = $this->getMockForAbstractClass(MongodbBase::class); # abstract -> concrete
        $this->reflection = new \ReflectionClass($this->stub); # protected -> public (make accessible)
    }

    public function test_createDatastring()
    {
        // test for protected function
        $_create = $this->reflection->getMethod('_create');
        $_create->setAccessible(true);

        // test data
        foreach ([
            [],
            [10],
            ['tea' => 'oolong'],
            ['alpha', 'bravo', 'charlie', 'delta'],
        ] as $sample_datastring_array) {
            // try to _create
            $_create_result = $_create->invoke($this->stub, $sample_datastring_array);

            // $_create_result['result'] is bool
            $this->assertInternalType('boolean', $_create_result['result']);

            if ($_create_result['result']) {
                // success _create
                $this->assertInternalType('string', $_create_result['detail']); # ObjectID as string
                $this->assertNotEmpty($_create_result['detail']); # ObjectID is not empty
                $this->assertInstanceOf(\MongoDB\Collection::class, $_create_result['collection']); # MongoDB collection
                $this->assertInstanceOf(\ArrayObject::class, $_create_result['document']); # created document (\ArrayObject)
            } else {
                // failed _create
                $this->assertInternalType('string', $_create_result['detail']); # Exception message
            }
        }
    }

    public function test_createFile()
    {
        // test for protected function
        $_create = $this->reflection->getMethod('_create');
        $_create->setAccessible(true);

        // test data
        foreach ([
            [],
            ['data' => 'THIS_IS_ARRAY_WITH_FILE'],
            ['alpha', 'bravo', 'charlie', 'delta'],
        ] as $sample_datastring_array) {
            // create test file
            $testfilepath = $this->getTmpfilePathFromBinary(self::TESTDATA_BINARY_TEXT);

            // try to _create
            $_create_result = $_create->invoke($this->stub, $sample_datastring_array, $testfilepath);

            // $_create_result['result'] is bool
            $this->assertInternalType('boolean', $_create_result['result']);

            if ($_create_result['result']) {
                // success _create
                $this->assertInternalType('string', $_create_result['detail']); # ObjectID as string
                $this->assertNotEmpty($_create_result['detail']); # ObjectID is not empty
                $this->assertInstanceOf(\MongoDB\Collection::class, $_create_result['collection']); # MongoDB collection
                $this->assertInstanceOf(\ArrayObject::class, $_create_result['document']); # created document (\ArrayObject)
                // try to reading created file
                $this->assertEquals(self::TESTDATA_BINARY_TEXT, $this->getTestFileBinary(new \MongoDB\BSON\ObjectId($_create_result['detail'])));
            } else {
                // failed _create
                $this->assertInternalType('string', $_create_result['detail']); # Exception message
            }
        }
    }

    public function test_deleteDatastring()
    {
        // test for protected function
        $_delete = $this->reflection->getMethod('_delete');
        $_delete->setAccessible(true);

        // test for success deletion
        foreach ([
            [],
            ['data' => 'UDON_RAMEN_SPAGHETTI'],
            ['echo', 'foxtrot', 'golf', 'hotel'],
        ] as $sample_datastring_array) {
            // create test data set on database
            $test_data_objectId = $this->createTestDatastring($sample_datastring_array);

            // try to _delete
            $_delete_result = $_delete->invoke($this->stub, (string)$test_data_objectId);

            // $_delete_result will returns true because $test_data_objectId is exist data
            $this->assertTrue($_delete_result);

            // check existance (expected 'not found' before _delete)
            $this->assertNull($this->getTestBSONDocument(parent::COLLECTION_DATASTRING, $test_data_objectId));
        }

        // test for failure deletion
        foreach (self::TESTDATA_INVALID_ID_ARRAY as $invalid_id) {
            // try to _delete
            $_delete_result = $_delete->invoke($this->stub, (string)$test_data_objectId);

            // $_delete_result will returns false because $test_data_objectId is dummy object ID
            $this->assertFalse($_delete_result);
        }
    }

    public function test_deleteFile()
    {
        // test for protected function
        $_delete = $this->reflection->getMethod('_delete');
        $_delete->setAccessible(true);

        // test for success deletion
        foreach ([
            [],
            ['tea' => 'green'],
        ] as $sample_datastring_array) {
            // create test file
            $testfilepath = $this->getTmpfilePathFromBinary(self::TESTDATA_BINARY_TEXT);

            // register test file on database
            $test_data_objectId = $this->createTestFile($testfilepath, 'test_lorem', $sample_datastring_array);

            // try to _delete
            $_delete_result = $_delete->invoke($this->stub, (string)$test_data_objectId);

            // $_delete_result will returns true because $test_data_objectId is exist data
            $this->assertTrue($_delete_result);

            // check existance (expected 'not found' before _delete)
            $this->assertNull($this->getTestBSONDocument(parent::COLLECTION_FILE, $test_data_objectId)); # as document
            $this->assertNull($this->getTestFileBinary($test_data_objectId)); # as file binary
        }
    }

    public function test_readByInvalidObjectId()
    {
        // test for protected function
        $_read = $this->reflection->getMethod('_read');
        $_read->setAccessible(true);

        foreach (self::TESTDATA_INVALID_ID_ARRAY as $invalid_id) {
            $this->assertNull($_read->invoke($this->stub, $invalid_id)); # MongodbBase::_read($invalid_id)
        }
    }

    public function test_readByValidObjectId()
    {
        // test for protected function
        $_read = $this->reflection->getMethod('_read');
        $_read->setAccessible(true);

        // create test data set (datastring)
        $testDatastringObjectId = (string)$this->createTestDatastring(['test' => 'foo!']);

        // create test data set (file)
        $testfilepath = $this->getTmpfilePathFromBinary(self::TESTDATA_BINARY_TEXT);
        $testFileObjectId = (string)$this->createTestFile($testfilepath, 'test_lorem_without_datastring');

        // MongodbBase::_read($testDatastringObjectId)
        $this->assertInstanceOf(\MongoDB\Model\BSONDocument::class, $_read->invoke($this->stub, $testDatastringObjectId));
        // MongodbBase::_read($testFileObjectId)
        $this->assertInstanceOf(\MongoDB\Model\BSONDocument::class, $_read->invoke($this->stub, $testFileObjectId));
    }

    public function test_readFileByValidObjectId()
    {
        // test for protected function
        $_readFile = $this->reflection->getMethod('_readFile');
        $_readFile->setAccessible(true);

        // create test data set on database
        $testfilepath = $this->getTmpfilePathFromBinary(self::TESTDATA_BINARY_TEXT);
        $testObjectId = (string)$this->createTestFile($testfilepath, 'test_lorem_without_datastring');

        $this->assertEquals(self::TESTDATA_BINARY_TEXT, $_readFile->invoke($this->stub, $testObjectId));
    }

    public function test_readFileByInvalidObjectId()
    {
        // test for protected function
        $_readFile = $this->reflection->getMethod('_readFile');
        $_readFile->setAccessible(true);

        // try for invalid IDs
        foreach (self::TESTDATA_INVALID_ID_ARRAY as $invalid_id) {
            // MongodbBase::_readFile($invalid_id)
            $this->assertEmpty($_readFile->invoke($this->stub, $invalid_id));
            $this->assertEquals('', $_readFile->invoke($this->stub, $invalid_id));
        }
    }

    public function test_existanceOfDatastring()
    {
        // test for protected function
        $_exists = $this->reflection->getMethod('_exists');
        $_exists->setAccessible(true);

        // create test data set on database
        $testObjectId = (string)$this->createTestDatastring(['test' => 'foo!']);

        // try exists
        $result1 = $_exists->invoke($this->stub, $testObjectId); # MongodbBase::_exists($testObjectId)
        $this->assertTrue($result1['result']);
        $this->assertEquals('Success', $result1['detail']);

        // try not exists
        $result2 = $_exists->invoke($this->stub, '11112222333344445555'); # MongodbBase::_exists('11112222333344445555')
        $this->assertFalse($result2['result']);
        $this->assertNotEquals('Success', $result2['detail']);
    }

    public function test_existanceOfFile()
    {
        // test for protected function
        $_exists = $this->reflection->getMethod('_exists');
        $_exists->setAccessible(true);

        // create test data set on database
        $testfilepath = $this->getTmpfilePathFromBinary(self::TESTDATA_BINARY_TEXT);
        $testObjectId1 = (string)$this->createTestFile($testfilepath, 'test_lorem_without_datastring');
        $testObjectId2 = (string)$this->createTestFile($testfilepath, 'test_lorem_with_datastring', ['waffle' => 'doughnut']);

        // try exists 1
        $result1 = $_exists->invoke($this->stub, $testObjectId1); # MongodbBase::_exists($testObjectId1)
        $this->assertTrue($result1['result']);
        $this->assertEquals('Success', $result1['detail']);

        // try exists 2
        $result2 = $_exists->invoke($this->stub, $testObjectId2); # MongodbBase::_exists($testObjectId2)
        $this->assertTrue($result2['result']);
        $this->assertEquals('Success', $result2['detail']);

        // try not exists
        $result3 = $_exists->invoke($this->stub, '56fad2c36118fd2e9820cfc1'); # MongodbBase::_exists('56fad2c36118fd2e9820cfc1')
        $this->assertFalse($result3['result']);
        $this->assertNotEquals('Success', $result3['detail']);
    }

    public function test_getCollection()
    {
        // test for protected function
        $getCollection = $this->reflection->getMethod('getCollection');
        $getCollection->setAccessible(true);

        // MongodbBase::getCollection('dataStrings') : standard collection
        $this->assertInstanceOf(\MongoDB\Collection::class, $getCollection->invoke($this->stub, 'dataStrings'));
        // MongodbBase::getCollection('fs.files') : GridFS collection (but accessible as standard collection)
        $this->assertInstanceOf(\MongoDB\Collection::class, $getCollection->invoke($this->stub, 'fs.files'));
        // MongodbBase::getCollection('hoge') : we can get unsupported collection, but not throws any exceptions
        $this->assertInstanceOf(\MongoDB\Collection::class, $getCollection->invoke($this->stub, 'hogeeeee'));
    }
}
