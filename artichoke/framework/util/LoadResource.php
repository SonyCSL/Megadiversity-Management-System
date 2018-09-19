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

namespace artichoke\framework\util;

use artichoke\framework\core\Server;

class LoadResource
{
    /**
     * Load file
     *
     * @param string $file_path : Load file path (string)
     *
     * @return bool : If File exits, return true
     */
    public function loadFileFromPath(string $file_path, Server $server): bool
    {
        if (is_readable($file_path)) {
            $ext = substr($file_path, strrpos($file_path, '.') + 1);
            $server->sendMimeType(strtolower($ext));
            readfile($file_path);
            return true;
        }

        return false;
    }

    /**
     * Load file from array
     *
     * @param array $pathArray : File paths (array)
     *
     * @return bool : If File exits, return true
     */
    public function loadFileFromArray(array $pathArray, Server $server): bool
    {
        foreach ($pathArray as $fullPath) {
            if ($this->loadFileFromPath($fullPath, $server)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Load file from array and exit after load.
     *
     * @param array $pathArray : File paths (array)
     */
    public function loadFileFromArrayAndExit(array $pathArray, Server $server)
    {
        if ($this->loadFileFromArray($pathArray, $server)) {
            return true;
        }

        $server->sendHttpStatusCode(404);
        return false;
    }
}
