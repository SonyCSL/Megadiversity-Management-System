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
use artichoke\framework\core\Requests;
use artichoke\framework\core\Server;

abstract class LoginController extends \artichoke\framework\abstracts\ControllerBase
{
    protected $usermodel = '\\artichoke\\framework\\models\\client\\User';
    private $session;
    private $server;

    public function __construct(string $viewPageName = 'login')
    {
        parent::__construct($viewPageName);
        $this->session = new Session();
        $this->server = new Server($_SERVER);
    }

    public function indexAction(array $args = [])
    {
        // $args[0] : redirect page URL
        // $args[1] : the full namespace of Usermodel
        //
        $request = new Requests($_REQUEST);
        $input_username = (string)$request->get('username');
        $input_userpswd = (string)$request->get('password');

        $this->usermodel = $args[1];
        // Set form option
        if (!empty($input_username)) {
            // If it has set username, jump to auth method
            $this->set('username', $input_username);
            $this->auth($input_username, $input_userpswd);
        } else {
            // If it has not set username, check session
            if ($this->session->loginStatus()) {
                // You have logged in and directly accessing /login
                $this->server->redirect();
            } else {
                // You are not logged in and set some parameters
                if ($args[0] !== '') {
                    $this->set('redirect', '/'.$args[0]);
                }
                $this->session->jumpAfterLogin($args[0]);
            }
        }
    }

    protected function auth(string $input_username, string $input_userpswd)
    {
        // DB model
        $user = new $this->usermodel($input_username);
        $exist = $user->exists();
        if ($exist === null) {
            // db connection error
            $this->set('error_connection', true);
        } elseif ($exist === false) {
            // user not found
            $this->set('error_username', true);
        } else {
            // Authentication
            if ($user->loginAuth($input_userpswd)) {
                $this->session->loggingIn($input_username);
                $this->server->redirect($this->session->jumpAfterLogin());
            } else {
                $this->set('error_password', true);
            }
        }
    }
}
