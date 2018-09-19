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

use artichoke\framework\core\Requests;
use artichoke\framework\core\Server;
use artichoke\framework\models\application\AppbuilderModel;

class IndexController extends \artichoke\framework\abstracts\ControllerBase
{
    private $request;

    public function __construct()
    {
        parent::__construct();
        $this->request = (new Requests($_REQUEST))->get();
    }

    public function indexAction(array $args = [])
    {
        if (isset($args[0]) && $args[0] === 'exec') {
            $result = $this->exec(new AppbuilderModel());

            if (empty($result)) {
                // empty string is success
                (new Server($_SERVER))->switchFQDN($this->request['app_fqdn']);
            } else {
                // failed to create new application
                $this->set('failedMes', $result);
            }
        }
    }

    private function exec(AppbuilderModel $build): string
    {
        $conf = $this->request;
        $conf['check_default'] = 'on';

        // input check
        $sc = $build->setConf($conf);
        if (!$sc[0]) {
            // conf array is invalid
            return (string)$sc[1];
        }

        // try to create app directory
        if ($build->mkdir() === false) {
            return 'Permission denied: couldn\'t create any application directory.';
        }

        // try to create files
        $mf = $build->mkfiles();
        if (!$mf[0]) {
            return (string)$mf[1];
        }

        // all success: return empty string
        return '';
    }
}
