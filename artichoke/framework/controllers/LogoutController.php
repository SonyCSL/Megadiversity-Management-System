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

abstract class LogoutController extends \artichoke\framework\abstracts\ControllerBase
{
    public function __construct()
    {
        // Override
    }

    public function indexAction(array $args = [])
    {
        (new Session())->loggingOut();
    }

    public function __destruct()
    {
        // Override
        (new Server($_SERVER))->redirect();
    }
}
