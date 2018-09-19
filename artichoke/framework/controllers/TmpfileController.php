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

namespace artichoke\framework\controllers;

use artichoke\framework\core\Session;
use artichoke\framework\core\Server;

class TmpfileController extends \artichoke\framework\abstracts\ControllerBase
{
    private $contentType;
    private $filename;
    private $resource;
    private $mode = 0;

    public function __construct()
    {
        // Override
    }

    public function indexAction(array $args = [])
    {
        // check params
        if (empty($args[0]) || empty($args[1]) || empty($args[2]) && $args[0] !== 'tmpfile') {
            $tf = (new Session())->tmpfile();
            if ($tf !== null) {
                $this->resource = $tf;
                $this->mode = 1;
            }
        } else {
            $this->contentType = $args[1];
            $fn = sys_get_temp_dir().'/'.$args[2];
            if (is_writable($fn)) {
                $this->mode = 2;
                $this->filename = $fn;
            }
        }
    }

    public function __destruct()
    {
        $server = new Server($_SERVER);
        if ($this->mode === 2) {
            // Send mime like 'pdf'
            $server->sendMimeType($this->contentType);
            @readfile($this->filename);
            unlink($this->filename);
        } elseif ($this->mode === 1) {
            $server->sendMimeType('bin');
            echo $this->resource;
        } else {
            // if not found
            $server->sendHttpStatusCode(404);
        }
    }
}
