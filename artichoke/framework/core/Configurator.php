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

namespace artichoke\framework\core;

use artichoke\framework\util\SearchFile;

final class Configurator
{
    private static $system_root = null;
    private static $app_dir = null;
    private static $seed = [];
    private static $app_list = [];
    private static $title_prefix = '';
    private static $title_suffix = '';
    private static $config = null;

    private function load($root, $force_path = array())
    {
        $searchFile = new SearchFile($root);

        if (empty($force_path)) {
            $file_path = $searchFile->searchFilePath('Seed.ini');
        } else {
            $file_path = $force_path;
        }

        foreach ($file_path as $key => $value) {
            $ini = parse_ini_file($value, true, INI_SCANNER_TYPED);
            $app_fqdn = isset($ini['APPLICATION']['app_fqdn']) ? $ini['APPLICATION']['app_fqdn'] : '';

            self::$app_list[] = [
                'app_name' => $key,
                'app_fqdn' => $app_fqdn,
            ];

            if ($ini['APPLICATION']['app_default'] === true) {
                self::$seed = $ini;
                self::$app_dir = $key;
            } else {
                if ($app_fqdn === (new Server($_SERVER))->myFQDN()) {
                    self::$seed = $ini;
                    self::$app_dir = $key;
                }
            }
        }
    }

    // System initialize method
    public function initialize(string $root, string $search, array $force_path = array())
    {
        self::$system_root = $root;
        $this->load($root.'/'.$search.'/', $force_path);

        // not found any app directory
        if (empty(self::$app_dir)) {
            return '';
        }
        // ----- FRAMEWORK -----
        //
        // -------- APP --------
        // read from ini file
        self::$config = self::$seed['APPLICATION'];
        self::$title_prefix = self::$seed['HTML_OPTION']['title_prefix'];
        self::$title_suffix = self::$seed['HTML_OPTION']['title_suffix'];
        // set database connection information
        \artichoke\framework\abstracts\MariadbBase::setConnector(
            new \mysqli(
                self::$seed['MARIADB_INFO']['host'],
                self::$seed['MARIADB_INFO']['username'],
                self::$seed['MARIADB_INFO']['passwd'],
                self::$seed['MARIADB_INFO']['dbname'],
                self::$seed['MARIADB_INFO']['port']
            )
        );
        \artichoke\framework\abstracts\MongodbBase::setDatabase(
            (new \MongoDB\Client(
                'mongodb://'.
                self::$seed['MONGODB_INFO']['username'].':'.
                self::$seed['MONGODB_INFO']['passwd'].'@'.
                self::$seed['MONGODB_INFO']['host'].':'.
                self::$seed['MONGODB_INFO']['port'].'/'.
                self::$seed['MONGODB_INFO']['dbname']
            ))->selectDatabase(self::$seed['MONGODB_INFO']['dbname'])
        );

        return self::$app_dir;
    }

    // Return variables
    public function read($var = null)
    {
        $res = '';
        if (is_string($var)) {
            switch ($var) {
                case 'system_root':
                    $res = self::$system_root;
                    break;
                case 'app_dir':
                    $res = self::$app_dir;
                    break;
                case 'title_prefix':
                    $res = self::$title_prefix;
                    break;
                case 'title_suffix':
                    $res = self::$title_suffix;
                    break;
                case 'config':
                    $res = self::$config;
                    break;
                default:
                    break;
            }
        }
        return $res;
    }

    // Return application list
    public function app_list(): array
    {
        return self::$app_list;
    }

    // Return seed list
    public function getSeed(): array
    {
        return self::$seed;
    }
}
