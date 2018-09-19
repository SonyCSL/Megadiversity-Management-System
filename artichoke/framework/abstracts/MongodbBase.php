<?php

/**
 *    Copyright 2018 Sony Computer Science Laboratories, Inc.
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace artichoke\framework\abstracts;

abstract class MongodbBase
{
    private $db;
    private $f_collection;
    private $d_collection;
    private $bucket;
    private static $database;

    public function __construct()
    {
        if (isset(self::$database)) {
            $this->db = self::$database;
            $this->f_collection = $this->db->selectCollection('fs.files');
            $this->d_collection = $this->db->selectCollection('dataStrings');
            $this->bucket = $this->db->selectGridFSBucket();
        } else {
            throw new \Exception('Database(\MongoDB\Database) has not set');
        }
    }

    /**
     * Create new entry.
     *
     * @param array  $document
     * @param string $filepath
     * @param string $filename
     *
     * @return array [bool result, string detail, object collection, object bucket, array document]
     */
    protected function _create(array $document, string $filepath = null, string $filename = 'untitled'): array
    {
        $result['result'] = true;

        if (empty($filepath) || !is_readable($filepath)) {
            // create as DATASTRINGS into dataStrings collection

            try {
                // try to store
                $insertOneResult = $this->d_collection->insertOne($document);
                // on success
                $new_bson_id_object = $insertOneResult->getInsertedId(); // \MongoDB\BSON\ObjectId
                $result['detail'] = (string)$new_bson_id_object; // \MongoDB\BSON\ObjectId::__toString()
                $result['collection'] = $this->d_collection;
                $result['document'] = $this->d_collection->findOne(['_id' => $new_bson_id_object]); // \ArrayObject
            } catch (\Exception $e) {
                // fail
                $result['result'] = false;
                $result['detail'] = $e->getMessage();
            }
        } else {
            // create as FILE into fs.files collection

            // additional file information
            $document['contentType'] = mime_content_type($filepath);
            // open file stream
            $stream = fopen($filepath, 'r');

            try {
                // try to store
                $new_bson_id_object = $this->bucket->uploadFromStream($filename, $stream);

                // update additional document (data body)
                // see https://docs.mongodb.com/manual/reference/operator/update/currentDate/#up._S_currentDate
                $this->f_collection->updateOne(
                    ['_id' => $new_bson_id_object],
                    ['$set' => $document]
                );
                // on success
                $result['detail'] = (string)$new_bson_id_object; // \MongoDB\BSON\ObjectId::__toString()
                $result['collection'] = $this->f_collection;
                $result['document'] = $this->f_collection->findOne(['_id' => $new_bson_id_object]); // \ArrayObject
            } catch (\Exception $e) {
                // fail
                $result['result'] = false;
                $result['detail'] = $e->getMessage();
            } finally {
                // close file stream
                fclose($stream);
            }
        }

        return $result;
    }

    /**
     * Read one document.
     *
     * @param string $document_id
     *
     * @return \MongoDB\Model\BSONDocument extends \ArrayObject|null
     */
    protected function _read(string $document_id)
    {
        $exist = $this->_exists($document_id);

        // file or datastrings
        if ($exist['result']) {
            // @return ArrayObject
            return $exist['collection']->findOne(['_id' => $exist['_id']]);
        } else {
            // @return null
            return;
        }
    }

    /**
     * Read file binary.
     *
     * @param string $document_id (on fs.files)
     *
     * @return string (in case $document_id is on dataStrings, returns empty string)
     */
    protected function _readFile(string $document_id): string
    {
        $fs = $this->_readFileStream($document_id);

        if ($fs === null) {
            return '';
        } else {
            return stream_get_contents($fs);
        }
    }

    /**
     * Read file stream.
     *
     * @param string $document_id (on fs.files)
     *
     * @return resource (in case $document_id is on dataStrings, returns false)
     */
    protected function _readFileStream(string $document_id)
    {
        $exist = $this->_exists($document_id);

        if ($exist['result'] && isset($exist['bucket'])) {
            return $exist['bucket']->openDownloadStream($exist['_id']);
        } else {
            return;
        }
    }

    /**
     * Delete one document.
     *
     * @param string $document_id
     *
     * @return boolean
     */
    protected function _delete(string $document_id): bool
    {
        $exist = $this->_exists($document_id);

        // file or datastrings
        if ($exist['result']) {
            if (isset($exist['bucket'])) {
                // $document_id is in fs.files
                $exist['bucket']->delete($exist['_id']);
            } else {
                // $document_id is in dataStrings
                $exist['collection']->deleteOne(['_id' => $exist['_id']]);
            }
        } else {
            // not found
            return false;
        }

        return true;
    }

    /**
     * Check existance of the document.
     *
     * @param string $document_id
     *
     * @return array [bool result, string detail, object idObject, object collection, object bucket, array document]
     */
    protected function _exists(string $document_id): array
    {
        $result = [
            'result' => true,
            'detail' => 'Success',
        ];

        // validate id
        try {
            $document_id_object = new \MongoDB\BSON\ObjectId($document_id);
        } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
            $result['result'] = false;
            $result['detail'] = $e->getMessage();
            return $result;
        }

        // id object
        $result['_id'] = $document_id_object;

        // find document
        $fs = $this->f_collection->findOne(['_id' => $document_id_object]);
        $ds = $this->d_collection->findOne(['_id' => $document_id_object]);

        // file or datastrings
        if ($fs !== null) {
            // $document_id is in fs.files
            $result['collection'] = $this->f_collection;
            $result['document'] = $fs;
            $result['bucket'] = $this->bucket;
        } elseif ($ds !== null) {
            // $document_id is in dataStrings
            $result['collection'] = $this->d_collection;
            $result['document'] = $ds;
        } else {
            // not found
            $result['result'] = false;
            $result['detail'] = 'Not exists';
        }

        return $result;
    }

    /**
     * Get MongoDB collection.
     *
     * @param string $collection 'fs.files' or 'dataStrings'
     *
     * @return \MongoDB\Collection
     */
    protected function getCollection(string $collection): \MongoDB\Collection
    {
        return $this->db->selectCollection($collection);
    }

    /**
     * Set connector object (\MongoDB\Database).
     *
     * @param \MongoDB\Database $databaseInstance
     */
    public static function setDatabase(\MongoDB\Database $databaseInstance)
    {
        self::$database = $databaseInstance;
    }
}
