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

class SearchFile
{
    const RELATIVE_PATH_POINT_NUMBER = 2;
    private $search_root;

    public function __construct(string $root)
    {
        $this->search_root = $root;
    }

    /**
     * Get filepath
     *
     * @param string $search_root : search start directory
     * @param string $file_name   : file name to search
     *
     * @return string if file exist, return file path, if file doen't exist, return ''
     */
    public function searchFilePath(string $file_name): array
    {
        $dir_list = scandir($this->search_root, 1);
        $dir_count = count($dir_list) - self::RELATIVE_PATH_POINT_NUMBER; // Remove path . and ..

        $res_array = array();
        for ($i = 0; $i < $dir_count; $i++) {
            $target_file_path = $this->search_root.$dir_list[$i].'/'.$file_name;

            if (is_readable($target_file_path)) {
                $res_array += array($dir_list[$i] => $target_file_path);
            }
        }

        return $res_array;
    }
}
