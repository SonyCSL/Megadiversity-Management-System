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

abstract class AnalyticsBase
{
    const REQUIRE_NONE = 0;
    const REQUIRE_ARRAY = 1;
    const REQUIRE_STRING = 2;
    const REQUIRE_FILEPATH = 4;
    private $required_params_type = 0;

    protected $n;
    protected $order;
    protected $params = null;

    public function __construct($n = 1, $order = -1)
    {
        $this->n = $n;
        $this->order = $order;
    }

    public function getParams()
    {
        return $this->params;
    }

    // use at a controller after extended
    public function requiredParams()
    {
        return $this->required_params_type;
    }

    public function requiredParamsWithString()
    {
        $ret = [];
        if ($this->required_params_type === 0) {
            return 'No any params';
        }
        if ($this->required_params_type & self::REQUIRE_ARRAY) {
            $ret[] = 'Array';
        }
        if ($this->required_params_type & self::REQUIRE_STRING) {
            $ret[] = 'String';
        }
        if ($this->required_params_type & self::REQUIRE_FILEPATH) {
            $ret[] = 'File path';
        }
        return implode(',', $ret);
    }

    public function setParams($params)
    {
        if ((is_array($params) && ($this->required_params_type & self::REQUIRE_ARRAY)) ||
             (is_string($params) && ($this->required_params_type & self::REQUIRE_STRING)) ||
             (is_readable($params) && ($this->required_params_type & self::REQUIRE_FILEPATH))) {
            $this->params = $params;
            return true;
        } elseif ($this->required_params_type === self::REQUIRE_NONE) {
            $this->params = null;
            return true;
        } else {
            $this->params = null;
            return false;
        }
    }

    // must run at extended class's __construct
    protected function setRequiredParams($type)
    {
        // $type:
        // REQUIRE_NONE | REQUIRE_ARRAY | REQUIRE_STRING | REQUIRE_FILEPATH
        $this->required_params_type = $type;
    }

    abstract public function run();
}
