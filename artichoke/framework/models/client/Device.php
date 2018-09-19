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

namespace artichoke\framework\models\client;

class Device extends \artichoke\framework\abstracts\MariadbBase
{
    private $_id = null;
    private $info = null;

    public function __construct($device_identify)
    {
        // $_id = 0 : Web browser
        parent::__construct();

        // is argument ID or ACCESSKEY
        switch (gettype($device_identify)) {
            case 'integer':
                // looking up by device_id
                $sql = 'SELECT * FROM device WHERE device_id = '.(string)$device_identify;
                break;
            case 'string':
                // looking up by accesskey
                $sql = "SELECT * FROM upload_identifier INNER JOIN device ON device.device_id = upload_identifier.device_id WHERE upload_identifier.accesskey = '$device_identify'";
                break;
            default:
                $sql = null;
        }

        // get information from database
        if ($sql !== null) {
            $this->info = $this->Q($sql)->fetch_assoc();
        } else {
            $this->info = null;
        }

        // existance
        if ($this->info !== null && isset($this->info['device_id'])) {
            $this->_id = (int)$this->info['device_id'];
        }
    }

    public function exists(): bool
    {
        return isset($this->info);
    }

    public function available(): bool
    {
        $d = $this->getInfo('is_disabled');
        if ($d === null) {
            return false;
        } else {
            return !(bool)$d;
        }
    }

    public function getInfo(string $key = null)
    {
        if (empty($key)) {
            return $this->info;
        } elseif ($this->info === null) {
            return;
        } elseif (isset($this->info[$key])) {
            return $this->info[$key];
        } else {
            return;
        }
    }

    public function getId(): int
    {
        return (int)$this->_id;
    }

    public function getName(): string
    {
        return (string)$this->getInfo('devicename');
    }

    public function getOwnerId(): int
    {
        return (int)$this->getInfo('owner_id');
    }

    public function getAlbumId(): int
    {
        return (int)$this->getInfo('album_id');
    }

    public function getGeoJsonArray(): array
    {
        if (!$this->exists()) {
            return [];
        } else {
            $geo = $this->Q('SELECT X(location), Y(location), altitude, attached_height, place FROM device WHERE device_id = '.(string)$this->_id)->fetch_row();
            if ((float)$geo[0] === 0.0 && (float)$geo[1] === 0.0) {
                // WIP
                // POINT(0 0) is 'not specified about default geo position'
            }
            return [
                'type' => 'Point',
                'coordinates' => [(float)$geo[0], (float)$geo[1]],
                'altitude' => (empty($geo[2])) ? null : (float)$geo[2],
                'groundHeight' => (empty($geo[3])) ? null : (int)$geo[3],
                'place' => (empty($geo[4])) ? null : $geo[4],
            ];
        }
    }

    public function __toString(): string
    {
        return (string)$this->_id;
    }
}
