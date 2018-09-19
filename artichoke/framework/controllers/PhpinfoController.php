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

use artichoke\framework\core\Configurator;
use artichoke\framework\core\Session;
use artichoke\framework\core\Server;

abstract class PhpinfoController extends \artichoke\framework\abstracts\ControllerBase
{
    public function __construct()
    {
        // Override
    }

    public function indexAction(array $args = [])
    {
        // read app config
        $config = (new Configurator())->read('config');
        $user_auth = $config['user_auth'];

        if ($user_auth) {
            // auth available
            $usermodel = $config['auth_user_model'];
            $myName = (new Session())->getLoginName();
            $user = new $usermodel($myName);
            if ($user->isAdmin()) {
                phpinfo();
            } else {
                (new Server($_SERVER))->sendHttpStatusCode(404);
            }
        } else {
            // no authentication
            phpinfo();
        }
    }

    public function __destruct()
    {
        // Override
    }
}
