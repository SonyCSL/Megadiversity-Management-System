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

use artichoke\framework\core\Server;

abstract class AsyncBase
{
    protected $test = false;
    private $format = 'json';
    private $variables = [];
    private $rawText = '';

    // Default: do not load any page template
    public function __construct()
    {
        // nothing to do
    }

    public function getAllParams()
    {
        return array(
            'test' => $this->test,
            'format' => $this->format,
            'variables' => $this->variables,
            'rawText' => $this->rawText,
        );
    }

    // Must be implemented
    abstract protected function indexAction(array $args = []);

    // Send a variable to view
    protected function set($key, $value)
    {
        $this->setFormat('json');
        $this->variables[$key] = $value;
    }
    protected function aSet($key, $value)
    {
        $this->setFormat('json');
        $this->variables[$key][] = $value;
    }
    protected function copy($value)
    {
        $this->setFormat('json');
        $this->variables = $value;
    }

    // Set output format
    protected function setFormat($type = 'text')
    {
        $this->format = $type;
    }

    // For direct echo (and set format to text)
    protected function respond($output = '')
    {
        $this->setFormat('text');
        $this->rawText .= (string)$output;
    }
    protected function respondln($output = '')
    {
        $this->setFormat('text');
        $this->rawText .= (string)$output."\n";
    }

    // Delete a variable or all from view
    protected function clear($key = null)
    {
        if (!isset($key)) {
            $this->variables = [];
            $this->rawText = '';
            $this->setFormat('?');
        } elseif (isset($this->variables[$key])) {
            unset($this->variables[$key]);
        }
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
        $server = new Server($_SERVER);
        if (!$server->fromAjax() && $this->test !== true) {
            // accept only ajax access
            $server->sendHttpStatusCode(403);
        } else {
            // output for each format
            switch ($this->format) {
                case 'json':
                    $server->sendMimeType('json');
                    echo json_encode($this->variables, JSON_UNESCAPED_UNICODE);
                    break;
                case 'text':
                    $server->sendMimeType('text');
                    echo $this->rawText;
                    break;
                default:
                    $server->sendHttpStatusCode(500);
                    break;
            }
        }
    }
}
