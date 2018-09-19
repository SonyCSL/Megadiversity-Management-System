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

namespace artichoke\framework\models\application;

use artichoke\framework\core\Configurator;

class AppbuilderModel
{
    private $conf = [];
    private $app_dir;
    private $root_dir;
    private $dirPermission = false;

    /**
     * Set app building configuration.
     *
     * @param array $conf array:
     *                    app_name (String, Required)
     *                    app_fqdn (String, Required)
     *                    check_default (Boolean, Optional)
     *                    check_api (Boolean, Optional)
     *                    check_auth (Boolean, Optional)
     *
     * @return array [bool result, string detail]
     */
    public function setConf(array $conf): array
    {
        // Check blank
        if (empty($conf['app_name'])) {
            return [false, 'Please set your application name'];
        }
        if (empty($conf['app_fqdn'])) {
            return [false, 'Please set your host.domain name'];
        }
        if (strtolower($conf['app_name']) === 'framework') {
            return [false, 'Couldn\'t use this application name (forbidden word)'];
        }
        // Check duplication
        $configurator = new Configurator();

        $app_list = $configurator->app_list();
        foreach ($app_list as $apps) {
            if ($apps['app_name'] === $conf['app_name']) {
                return [false, 'Couldn\'t use this application name (already exists)'];
            }
            if ($apps['app_fqdn'] === $conf['app_fqdn']) {
                return [false, 'Couldn\'t use this FQDN (already exists)'];
            }
        }

        $this->conf = $conf;
        $this->root_dir = $configurator->read('system_root');
        $this->app_dir = $this->root_dir.'/artichoke/'.$this->conf['app_name'];
        return [true, ''];
    }

    /**
     * Create directory for new application.
     *
     * @return boolean result
     */
    public function mkdir(): bool
    {
        if (empty($this->app_dir) || !mkdir($this->app_dir, 0755)) {
            return false;
        }
        // create app mvc
        foreach ([
            '/models',
            '/views',
            '/views/protected',
            '/views/protected/resources',
            '/views/public',
            '/views/public/js',
            '/views/public/css',
            '/views/template',
            '/controllers',
        ] as $path) {
            $create = mkdir($this->app_dir.$path, 0755);
            if (!$create) {
                return false;
            }
        }
        $this->dirPermission = true;
        return true;
    }

    /**
     * Create files for new application.
     *
     * @return array [bool result, string detail]
     */
    public function mkfiles(): array
    {
        // loading Seed.ini.skeleton
        $baseIni = file_get_contents(__DIR__.'/Seed.ini.skeleton');
        if ($baseIni === false) {
            return [false, 'Framework error: couldn\'t read template file for Seed.ini'];
        }

        // create and write new Seed.ini
        $check_default = isset($this->conf['check_default']) ? 'true' : 'false';
        $check_api = isset($this->conf['check_api']) ? 'true' : 'false';
        $check_auth = isset($this->conf['check_auth']) ? 'true' : 'false';
        $newIni = "[APPLICATION]\napp_fqdn = \"".$this->conf['app_fqdn']."\"\napp_default = $check_default\napi_available = $check_api\nuser_auth = $check_auth\n";
        $r = file_put_contents($this->app_dir.'/Seed.ini', $newIni.$baseIni);
        if ($r === false) {
            return [false, 'Couldn\'t write configure strings to '.$this->app_dir.'/Seed.ini'];
        }

        // copy html template
        $r = copy(__DIR__.'/index.html.skeleton', $this->app_dir.'/views/template/index.html');
        if ($r === false) {
            return [false, 'Couldn\'t copy HTML template to '.$this->app_dir.'/views/template/index.html'];
        }

        // copy resources
        $fl = scandir(__DIR__, 1);
        for ($i = 0; $i < count($fl) - 2; $i++) {
            $ext = explode('.', $fl[$i]);
            if ($ext[1] === 'png') {
                $r = copy(__DIR__.'/'.$fl[$i], $this->app_dir.'/views/protected/resources/'.$fl[$i]);
                if ($r === false) {
                    return [false, 'Couldn\'t copy sample image file to '.$this->app_dir.'/views/protected/resources/'.$fl[$i]];
                }
            }
        }

        // loading IndexController.php.skeleton
        $baseCtrlr = file_get_contents(__DIR__.'/IndexController.php.skeleton');
        if ($baseCtrlr === false) {
            return [false, 'Framework error: couldn\'t read template file for IndexController.php'];
        }

        // add namespace to new controller
        $newCtrlr = "<?php\n\nnamespace artichoke\\".$this->conf['app_name']."\\controllers;\n\n";
        $r = file_put_contents($this->app_dir.'/controllers/IndexController.php', $newCtrlr.$baseCtrlr);
        if ($r === false) {
            return [false, 'Couldn\'t add new namespace to IndexController'];
        }

        // All success
        return [true, ''];
    }
}
