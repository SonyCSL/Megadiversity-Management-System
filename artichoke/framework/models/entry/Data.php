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

class Data
{
    const UNIT_UNKNOWN = '';
    private $data = [];
    private $parse_error = '';

    public function __construct($data_raw)
    {
        if (gettype($data_raw) === 'array') {
            // type of array: directly set
            $this->data = $data_raw;
        } elseif ($data_raw instanceof \ArrayObject) {
            // convert from \ArrayObject to array
            $this->data = $data_raw->getArrayCopy();
        } elseif (gettype($data_raw) === 'string') {
            // convert from string to array
            $json_trimmed = str_replace(["\n", "\r", ' '], '', $data_raw);
            $this->data = json_decode($json_trimmed, true);
            if ($this->data === null && (json_last_error() !== JSON_ERROR_NONE)) {
                // error message
                $this->parse_error = json_last_error_msg();
                $this->data = [];
            }
        } else {
            $this->data = (array)$data_raw; // include empty values (null, false, 0, '')
        }
    }

    /**
     * Get a datum associated with $key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key, $with_unit = false)
    {
        $res = null;
        if (isset($this->data[$key])) {
            if (gettype($this->data[$key]) === 'array') {
                // standard structure
                if (isset($this->data[$key]['value'])) {
                    if ($with_unit) {
                        // with unit (string)
                        $res = (string)$this->data[$key]['value'].$this->getUnit($key);
                    } else {
                        // only value (mixed)
                        $res = $this->data[$key]['value'];
                    }
                } else {
                    return;
                }
            } else {
                // single value (old style)
                $res = $this->data[$key];
            }
        } else {
            return;
        }

        return $res;
    }

    public function getInt(string $key): int
    {
        return intval($this->get($key));
    }

    public function getFloat(string $key): float
    {
        return floatval($this->get($key));
    }

    public function getBool(string $key): bool
    {
        return boolval($this->get($key));
    }

    public function getString(string $key): string
    {
        return strval($this->get($key));
    }

    /**
     * Get a datum unit string associated with $key.
     *
     * @param string $key
     *
     * @return string
     */
    public function getUnit(string $key): string
    {
        if (isset($this->data[$key]) && isset($this->data[$key]['unit'])) {
            return (string)$this->data[$key]['unit'];
        } else {
            return '';
        }
    }

    /**
     * Get keys contains on the data.
     *
     * @return array
     */
    public function getKeys(): array
    {
        return array_keys($this->data);
    }

    public function getParseError(): string
    {
        return $this->parse_error;
    }

    /**
     * Text expression for all of data.
     * Useful in overviewing the data.
     *
     * @param string $separator   : glue string between any data
     * @param string $alternative : alternative text if this is empty data (contains NULL data)
     *
     * @return string
     */
    public function toText(string $separator = "\n", string $alternative = null): string
    {
        $pre_array = [];
        foreach ($this->getKeys() as $key) {
            $pre_array[] = $key.': '.$this->get($key, true);
        }

        if (empty($pre_array)) {
            // if no data
            return (string)$alternative;
        } else {
            return implode($separator, $pre_array);
        }
    }

    /**
     * Set one datum with $key
     *
     * @param string  $key
     * @param mixed   $value
     * @param integer $unit
     */
    public function set(string $key, $value, string $unit = self::UNIT_UNKNOWN)
    {
        $this->data[$key]['value'] = $value;
        $this->data[$key]['unit'] = $unit;
    }

    /**
     * Return JSON data string
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    /**
     * Return data array:
     * Use at writing to database as a entry.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get text expression about the data.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toText();
    }
}
