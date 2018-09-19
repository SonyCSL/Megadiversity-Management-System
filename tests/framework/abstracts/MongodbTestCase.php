<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;

abstract class MongodbTestCase extends TestCase
{
    const COLLECTION_DATASTRING = 'dataStrings';
    const COLLECTION_FILE = 'fs.files';
    private static $client = null;
    private static $db = null;
    private static $bucket = null;
    private static $tmpfiles = [];

    /**
     * @doesNotPerformAssertions
     */
    public static function getTestClient(): \MongoDB\Client
    {
        if (self::$client === null) {
            self::$client = new \MongoDB\Client(
                'mongodb://'.
                $GLOBALS['MONGODB_USER'].':'.
                $GLOBALS['MONGODB_PASSWD'].'@'.
                $GLOBALS['MONGODB_HOST'].':'.
                $GLOBALS['MONGODB_PORT'].'/'.
                $GLOBALS['MONGODB_DBNAME']
            );
        }

        return self::$client;
    }

    /**
     * @doesNotPerformAssertions
     */
    public static function getTestDatabase(): \MongoDB\Database
    {
        if (self::$db === null) {
            self::$db = self::getTestClient()->selectDatabase($GLOBALS['MONGODB_DBNAME']);
        }

        return self::$db;
    }

    /**
     * @doesNotPerformAssertions
     */
    public static function getBucket(): \MongoDB\GridFS\Bucket
    {
        if (self::$bucket === null) {
            self::$bucket = self::getTestDatabase()->selectGridFSBucket();
        }

        return self::$bucket;
    }

    public static function setUpBeforeClass()
    {
        /**
         * if need explicitly create collection for test, uncomment below:
         *
         * @see https://docs.mongodb.com/php-library/current/reference/method/MongoDBDatabase-createCollection/
         */

        // self::getTestDatabase()->createCollection(self::COLLECTION_DATASTRING);
    }

    public static function tearDownAfterClass()
    {
        // delete gridfs bucket
        self::getBucket()->drop();
        // delete all collections in test database
        foreach (self::getTestDatabase()->listCollections() as $collectionInfo) {
            self::getTestDatabase()->dropCollection($collectionInfo->getName());
        }
        // delete temporary files
        foreach (self::$tmpfiles as $tmpfile) {
            if (is_writable($tmpfile)) {
                unlink($tmpfile);
            }
        }
    }

    /**
     * @doesNotPerformAssertions
     */
    public function getRandomBinary(int $bytes): string
    {
        $bin = '';
        for ($i = 0; $i < $bytes; $i++) {
            $bin .= chr(rand(0, 255));
        }
        return $bin;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function getTmpfilePathFromBinary(string $binary): string
    {
        $newTmpfilePath = tempnam(sys_get_temp_dir(), 'MMS_MONGODB_TESTFILE_');
        file_put_contents($newTmpfilePath, $binary);
        self::$tmpfiles[] = $newTmpfilePath;
        return $newTmpfilePath;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function createTestDatastring(array $document): \MongoDB\BSON\ObjectId
    {
        return self::getTestDatabase()->selectCollection(self::COLLECTION_DATASTRING)->insertOne($document)->getInsertedId();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function createTestFile(string $filepath, string $filename, array $document = []): \MongoDB\BSON\ObjectId
    {
        $new_objectId = self::getBucket()->uploadFromStream($filename, fopen($filepath, 'r'));
        if (!empty($document)) {
            self::getTestDatabase()->selectCollection(self::COLLECTION_FILE)->updateOne(
                ['_id' => $new_objectId],
                ['$set' => $document]
            );
        }
        return $new_objectId;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function deleteTestDatastring(\MongoDB\BSON\ObjectId $objectId)
    {
        self::getTestDatabase()->selectCollection(self::COLLECTION_DATASTRING)->deleteOne(['_id' => $objectId]);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function deleteTestFile(\MongoDB\BSON\ObjectId $objectId)
    {
        self::getBucket()->delete($objectId);
    }

    /**
     * @doesNotPerformAssertions
     *
     * @return \MongoDB\Model\BSONDocument | null
     */
    public function getTestBSONDocument(string $collection, \MongoDB\BSON\ObjectId $objectId)
    {
        return self::getTestDatabase()->selectCollection($collection)->findOne(['_id' => $objectId]);
    }

    /**
     * @doesNotPerformAssertions
     *
     * @return string | null
     */
    public function getTestFileBinary(\MongoDB\BSON\ObjectId $objectId)
    {
        try {
            $stream = self::getBucket()->openDownloadStream($objectId);
        } catch (\MongoDB\GridFS\Exception\FileNotFoundException $e) {
            return;
        }
        return stream_get_contents($stream);
    }
}
