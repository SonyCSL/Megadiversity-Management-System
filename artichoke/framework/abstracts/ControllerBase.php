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

use artichoke\framework\core\Generator;
use artichoke\framework\core\Requests;
use artichoke\framework\core\Server;

abstract class ControllerBase
{
    private $dump = false;
    private $output;
    private $pageVariables = [];
    private $pageArrays = [];

    // Default: loads same name html from /views/html
    public function __construct(string $viewPageName = 'index')
    {
        $this->output = new Generator($viewPageName);
    }

    // Must be implemented
    abstract protected function indexAction(array $args = []);

    //for unittest
    public function getAllParams()
    {
        return array(
            'dump' => $this->dump,
            'pageVariables' => $this->pageVariables,
            'pageArrays' => $this->pageArrays,
        );
    }

    // Send a variable to view
    protected function set(string $key, $value)
    {
        $this->pageVariables[$key] = $value;
    }
    protected function aSet(string $key, $value)
    {
        $this->pageArrays[$key][] = $value;
    }

    // $ value is type of array | \ArrayObject
    protected function aCopy(string $key, $value)
    {
        // $value is 1-dimention
        // $array_var[index]
        $this->pageArrays[$key] = $value;
    }

    // Delete a variable or all from view
    protected function clear(string $key = null)
    {
        if (!isset($key)) {
            $this->pageVariables = [];
        } elseif (isset($this->pageVariables[$key])) {
            unset($this->pageVariables[$key]);
        }
    }

    // Delete a array or all from view
    protected function aClear(string $key = null)
    {
        if (!isset($key)) {
            $this->pageArrays = [];
        } elseif (isset($this->pageArrays[$key])) {
            unset($this->pageArrays[$key]);
        }
    }

    // Echo template variables
    protected function showDump()
    {
        $this->dump = true;
    }

    // Reloading(reset&read) view template
    protected function reload(string $viewPageName)
    {
        $this->output->reset($viewPageName); // this is Generator's method
    }

    // Throw exception
    protected function exception($args = null)
    {
        /* ############ args array ############
        // $args[0] : Exception Code
        // $args[1] : Exception statement
        // $args[2] : Target ClassName
        // $args[3] : Target MethodName
        // $args[4] : Requested parameters
        // $args[5] : HTTP status code
        // ########## Exception Code ##########
        0. Unknown Error (Not exception)
        1. Requested Controller is not found
        2. Requested ActionMethod is not found
        3. Not accepted direct accessing to .php file
        4. Requested ID is not found at Database
        5. Resource is not found or unavailable
        6.
        */ ########## -------------- ##########
        $this->clear();
        $this->aClear();
        $this->reload('exception');
        if (!is_array($args)) {
            $args = [
                0,
                'Unknow exception is thrown',
                get_class($this),
                __FUNCTION__,
                'unknown',
                500,
            ];
        }
        if (isset($args[5])) {
            (new Server($_SERVER))->sendHttpStatusCode($args[5]);
        }
        $this->set('detail', $args[1]);
        $this->set('exCode', $args[0]);
        $this->set('params', $args[4]);
        return;
    }

    // Rendering and output as webpage
    public function __destruct()
    {
        (new Server($_SERVER))->sendMimeType('html');
        echo $this->output->run($this->pageVariables, $this->pageArrays, (new Requests($_REQUEST))->get('view'));
        // For debug
        if ($this->dump) {
            echo 'Single variables:';
            var_dump($this->pageVariables);
            echo 'Arrays:';
            var_dump($this->pageArrays);
        }
    }
}
