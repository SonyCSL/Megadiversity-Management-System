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

use artichoke\framework\core\Server;

class ExceptionController extends \artichoke\framework\abstracts\ControllerBase
{
    public function indexAction(array $args = [])
    {
        // Processes general error
        // $args[0] : Exception Code
        // $args[1] : Exception statement
        // $args[2] : Target ClassName
        // $args[3] : Target MethodName
        // $args[4] : Requested parameters
        // $args[5] : HTTP status code
        //
        if (isset($args[5])) {
            (new Server($_SERVER))->sendHttpStatusCode($args[5]);
        }
        $this->set('detail', $args[1]);
        $this->set('exCode', $args[0]);
        $this->set('params', $args[4]);
    }
}

/* ########## Exception Code ##########
0. Unknown Error (Not exception)
1. Requested Controller is not found
2. Requested ActionMethod is not found
3. Not accepted direct accessing to .php file
4. Requested ID is not found at Database
5.
*/ ########## Exception Code ##########
