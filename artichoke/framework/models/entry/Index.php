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

namespace artichoke\framework\models\entry;

class Index extends \artichoke\framework\abstracts\MongodbBase
{
    const DATASTRINGS = 1;
    const FILES = 2;
    private $type;
    private $target_collection;

    public function __construct(int $any_collections = self::FILES)
    {
        parent::__construct();
        $this->type = $any_collections;
        if ($this->type & self::DATASTRINGS) {
            $this->target_collection = $this->getCollection('dataStrings');
        } else {
            $this->target_collection = $this->getCollection('fs.files');
        }
    }

    /**
     * Pickup 1 entry from specific album.
     *
     * @param integer $album_id
     *
     * @return array [string _id, int count, string countString]
     */
    public function sample(int $album_id): array
    {
        $result = [];
        $result['count'] = $this->target_collection->count(['album_id' => $album_id]);

        // suffixes for count
        if ($this->type & self::DATASTRINGS) {
            $cs_suffix = ' string';
        } else {
            $cs_suffix = ' file';
        }

        // convert count to string
        if ($result['count'] === 1) {
            $result['countString'] = '1'.$cs_suffix;
        } else {
            $result['countString'] = (string)$result['count'].$cs_suffix.'s';
        }

        // pickup entry
        $pickup = $this->target_collection->findOne(['album_id' => $album_id], ['sort' => ['uploadDate' => -1]]);
        if (isset($pickup)) {
            $result['_id'] = $pickup['_id'];
        } else {
            $result['_id'] = null;
        }

        return $result;
    }

    /**
     * Find the documents contains in the album id.
     *
     * @param integer $album_id
     * @param integer $max
     * @param integer $offset
     * @param string  $sort
     * @param integer $order
     *
     * @return Iterable
     */
    public function listup(int $album_id, int $max = 100, int $offset = 0, string $sort = 'uploadDate', int $order = -1): Iterable
    {
        // search(find)
        return $this->find(['album_id' => $album_id], $max, $offset, $sort, $order);
    }

    /**
     * Find the documents by the condition array.
     *
     * @param array   $filter
     * @param integer $max
     * @param integer $offset
     * @param string  $sort
     * @param integer $order
     *
     * @return Iterable
     */
    public function find(array $filter = [], int $max = 100, int $offset = 0, string $sort = 'uploadDate', int $order = -1): Iterable
    {
        $samples = $this->target_collection->find($filter, ['sort' => [$sort => (int)$order], 'limit' => $max]);

        // Listing
        foreach ($samples as $sample) {
            $result_line['_id'] = (string)$sample['_id'];
            $result_line['_id_short'] = substr((string)$sample['_id'], 0, 8);
            $result_line['upload_datetime'] = $sample['uploadDate']->toDateTime(); // object \DateTime
            if (isset($sample['data'])) {
                // if contains data string
                // object \artichoke\framework\models\entry\Data
                $result_line['data'] = new Data($sample['data']);
            } else {
                $result_line['data'] = new Data([]);
            }
            if ($this->type & self::FILES) {
                // only at fs.files
                $result_line['name'] = (isset($sample['filename'])) ? $sample['filename'] : null;
                $result_line['content_type'] = (isset($sample['contentType'])) ? $sample['contentType'] : 'Unknown';
            }
            yield $result_line;
        }
    }
}
