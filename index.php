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

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = __DIR__;

// Class autoloader (by composer)
require_once $root.'/vendor/autoload.php';

$configurator = new \artichoke\framework\core\Configurator();
$server = new \artichoke\framework\core\Server($_SERVER);

// Environment Configuration
$app = $configurator->initialize($root, 'artichoke');

// Controller dispatching
\artichoke\framework\core\Dispatcher::run($configurator, $server, $app);
