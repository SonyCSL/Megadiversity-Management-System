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

class Entry extends \artichoke\framework\abstracts\MongodbBase
{
    const INVALID = 0;
    const EDITABLE = 1;
    const CREATABLE = 3; // EDITABLE + 2
    const READABLE = 4;
    const UPDATABLE = 5; // EDITABLE + READABLE

    private $_id = null;
    private $state = self::INVALID;
    private $existance = false;
    /**
     * default values for new document:
     * please use as template.
     * 'geo' is GeoJSON format (Point type) supports. ('location' is deprecated)
     *
     * @see https://docs.mongodb.com/manual/reference/geojson/
     */
    private $document = [
        'album_id' => null,
        'device_id' => null,
        'public' => true,
        'lock' => false,
        'uploadMethod' => 'UNKNOWN',
    ];
    private $target_album_id = 0;   // for CREATABLE
    private $target_device_id = 0;  // for CREATABLE
    private $filepath = null;       // for CREATABLE
    private $filename = 'untitled'; // for CREATABLE

    /**
     * Constructor for Entry.
     *
     *  $entry_id    │ instance
     * ──────────────┼──────────────────────────
     *  NULL         │ Create new entry
     *  Valid MongoId│ Read or Update the entry
     *  Invalid one  │ None
     *
     * @param string $entry_id
     */
    public function __construct(string $entry_id = null)
    {
        parent::__construct();

        // instace set for CREATE or READ
        if (empty($entry_id)) {
            // for create new entry
            $this->state = self::CREATABLE;
        } else {
            // for read or edit already existed entry
            // try to read from database
            $doc = $this->_read($entry_id);
            if ($doc === null) {
                // cannot read (wrong id)
                $this->state = self::INVALID;
            } else {
                // ok.
                $this->_id = $entry_id;
                $this->document = $doc; // Overwrite default values
                $this->existance = true;
                if (isset($doc['lock']) && $doc['lock'] === true) {
                    // update forbidden
                    $this->state = self::READABLE;
                } else {
                    // updatable
                    $this->state = self::UPDATABLE;
                }
            }
        }
    }

    /**
     * Create new entry.
     *
     * @return array result
     */
    public function create(): array
    {
        $this->setDocument('album_id', $this->target_album_id);
        $this->setDocument('device_id', $this->target_device_id);
        $this->setDocument('uploadDate', new \MongoDB\BSON\UTCDateTime()); // for Datastrings (GridFS automatically contains uploadDate)

        $res = [];
        if ($this->state !== self::CREATABLE) {
            // _id is already used or invalid
            $res = [false, 'This entry ID is already used or invalid'];
        } elseif (empty($this->document['album_id'])) {
            // album_id must be integer and larger than 0
            $res = [false, 'Invalid target album id'];
        } elseif (empty($this->document['device_id'])) {
            // device_id must be integer and larger than 0
            $res = [false, 'Invalid associated device id'];
        } else {
            // try to create the document
            $new = $this->_create($this->document, $this->filepath, $this->filename);
            $res = [$new['result'], $new['detail'], $new['document']];
        }

        return $res;
    }

    public function exists(): bool
    {
        return $this->existance;
    }

    public function delete(): bool
    {
        if ($this->existance) {
            return $this->_delete($this->_id);
        } else {
            return false;
        }
    }

    public function setDocument(string $key, $value)
    {
        // $value may contains null, 0, empty string, empty array
        if ($this->state & self::EDITABLE) {
            $this->document[$key] = $value;
        }
    }

    public function addDocument(string $key, $value)
    {
        if ($this->state & self::EDITABLE) {
            if (gettype($this->document[$key]) === 'array') {
                if (gettype($value) === 'array') {
                    $this->document[$key] = array_merge($this->document[$key], $value);
                } else {
                    $this->document[$key][] = $value;
                }
            } elseif (gettype($this->document[$key]) === 'string' && gettype($value) !== 'array') {
                $this->document[$key] .= (string)$value;
            } else {
                // nothing to do
            }
        }
    }

    public function setFile(string $path, string $name = 'untitled')
    {
        $this->filepath = $path;
        $this->filename = $name;
    }

    public function setAlbumId(int $aid)
    {
        $this->target_album_id = $aid;
    }

    public function setDeviceId(int $did)
    {
        $this->target_device_id = $did;
    }

    public function setUploadMethod(string $method)
    {
        $this->setDocument('uploadMethod', $method);
    }

    public function setDatetime($time)
    {
        if (gettype($time) === 'string') {
            $this->setDocument('userDate', new \MongoDB\BSON\UTCDateTime(new \DateTime($time)));
        } elseif (gettype($time) === 'integer') {
            $this->setDocument('userDate', new \MongoDB\BSON\UTCDateTime((new \DateTime())->setTimestamp($time)));
        } elseif ($time instanceof \DateTime) {
            $this->setDocument('userDate', new \MongoDB\BSON\UTCDateTime($time));
        } else {
            // nothing to do
        }
    }

    public function setTags($tags)
    {
        if (!empty($tags)) {
            if (gettype($tags) === 'array') {
                $this->setDocument('tags', $tags);
            } elseif (gettype($tags) === 'string') {
                $this->setDocument('tags', explode(',', mb_convert_encoding(trim($tags, ' ,'), 'UTF-8', 'auto')));
            } else {
                // nothing to do
            }
        }
    }

    public function addTags($tags)
    {
        if (!empty($tags)) {
            if (gettype($tags) === 'array') {
                $this->addDocument('tags', $tags);
            } elseif (gettype($tags) === 'string') {
                $this->addDocument('tags', explode(',', mb_convert_encoding(trim($tags, ' ,'), 'UTF-8', 'auto')));
            } else {
                // nothing to do
            }
        }
    }

    // optional
    // @see https://docs.mongodb.com/manual/tutorial/model-time-data/
    public function setTimezone(float $timezone)
    {
        $this->setDocument('timezone', $timezone);
    }

    public function setComment(string $comment)
    {
        if (!empty($comment)) {
            $this->setDocument('comment', $comment);
        }
    }

    public function setThumbnail(string $base64 = null)
    {
        if (!empty($base64)) {
            $this->setDocument('thumbnailB64', $base64);
        }
    }

    public function setMetadata(array $meta = null)
    {
        if (!empty($meta)) {
            $this->setDocument('meta', $meta);
        }
    }

    public function setGeoJsonArray(array $geojsonArray = null)
    {
        if (!empty($geojsonArray)) {
            $this->setDocument(
                'geo',
                array_merge(
                    [
                        'type' => 'Point',
                        'coordinates' => [0, 0],
                        'place' => null,
                        'local_position' => null,
                        'altitude' => null,
                        'groundHeight' => null,
                    ],
                    $geojsonArray
                )
            );
        }
    }

    public function setData(Data $data)
    {
        $this->setDocument('data', $data->toArray());
    }

    /**
     * Get document array.
     *
     * @param string $key
     *
     * @return array|string|null
     */
    public function getDocument(string $key = null)
    {
        if (!($this->state & self::READABLE)) {
            // invalid id (instance)
            return;
        }
        if (empty($key)) {
            // return all array
            return $this->document;
        } elseif (isset($this->document[$key])) {
            // string
            return $this->document[$key];
        } else {
            // $key is valid but not set on $this->info as key
            return;
        }
    }

    /**
     * Get document data.
     * The data is contained in $this->document['data'] as array.
     * This data and structure used at both of FILE and DATASTRING.
     *
     * @return Data
     */
    public function getData(): Data
    {
        $data = $this->getDocument('data');
        if (empty($data)) {
            // not set: empty data instance
            return new Data([]);
        } else {
            return new Data($data);
        }
    }

    public function getId(): string
    {
        return (string)$this->_id;
    }

    public function getAlbumId(): int
    {
        return (int)$this->getDocument('album_id');
    }

    public function getDeviceId(): int
    {
        return (int)$this->getDocument('device_id');
    }

    public function getUserEpoch()
    {
        $ud = $this->getDocument('userDate');
        return ($ud !== null) ? $ud->toDateTime()->getTimestamp() : null;
    }

    public function getUserDateTime()
    {
        $ude = $this->getUserEpoch();
        return ($ude !== null) ? (new \DateTime())->setTimestamp($ude) : null;
    }

    public function getUploadEpoch(): int
    {
        return $this->getDocument('uploadDate')->toDateTime()->getTimestamp();
    }

    public function getUploadDateTime(): \Datetime
    {
        return (new \DateTime())->setTimestamp($this->getUploadEpoch());
    }

    public function getUploadMethod(): string
    {
        return (string)$this->getDocument('uploadMethod');
    }

    public function getTags(string $addStringIfNoTags = null): array
    {
        $t = (array)$this->getDocument('tags');
        if (empty($t)) {
            // null || empty array
            if ($addStringIfNoTags !== null) {
                return [(string)$addStringIfNoTags];
            } else {
                return [];
            }
        } else {
            // array cast (tags array is 1-dimension)
            return $t;
        }
    }

    // optional
    public function getTimezone(): float
    {
        return (float)$this->getDocument('timezone');
    }

    public function getComment(string $setStringIfNoComments = ''): string
    {
        $c = $this->getDocument('comment');
        if (empty($c)) {
            // null || empty string
            return $setStringIfNoComments;
        } else {
            return (string)$c;
        }
    }

    public function __call($method, $arguments): string
    {
        return 'Not supported method is called';
    }

    public function __toString(): string
    {
        return (string)$this->_id;
    }
}
