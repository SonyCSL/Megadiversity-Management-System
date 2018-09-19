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

namespace artichoke\framework\util;

class AccessControllerApi
{
    const URL_METHOD_OFFSET = 1;
    const URL_PARAMETER_OFFSET = 2;

    /**
     * Init Controller Params
     *
     * @return string : Initial Controller parameters
     */
    public function InitControllerParams(): array
    {
        $res = array();

        // array for controller
        $res['ctrlr'] = ''; // Controller class.
        $res['actmd'] = ''; // action method.
        $res['pages'] = ''; // HTML templete (view) with controller.
        $res['param'] = []; // arguments for action method.
        $res['path'] = '';  // FullPath to [*]Contoroller.php

        return $res;
    }

    /**
     * Search Controller from URL
     *
     * @param array  $urlParams    : URL
     * @param bool   $is_framework : Search Controller from framework or not
     * @param bool   $root_dir     : MMS root
     * @param string $app_dir      : App dir name (ex. artichoke/$app_dir)
     *
     * @return string : Controller name space
     */
    public function searchControllerFromURL(array $urlParams, bool $is_framework, string $root_dir, string $app_dir): array
    {
        $get_paths = new GetPaths();
        $get_namespace = new GetNameSpace();
        if ($is_framework) {
            // Getting started - no app
            $ctrlr_file_rootdir = $get_paths->getFrameworkControllerPath($root_dir);
            $ctrlr_namespace_root = $get_namespace->getFrameworkController();
        } else {
            // Resolving address recursively
            $ctrlr_file_rootdir = $get_paths->getAppControllerPath($root_dir, $app_dir);
            $ctrlr_namespace_root = $get_namespace->getAppController($app_dir);
        }

        $current_dir = '';
        $depth = count($urlParams);

        $res = $this->InitControllerParams();
        for ($i = 0; $i < $depth; $i++) {
            $ctrlr_file_fullpath = $ctrlr_file_rootdir.$current_dir.ucfirst(strtolower($urlParams[$i])).'Controller.php';

            if (is_readable($ctrlr_file_fullpath)) {
                $res['pages'] = $current_dir.strtolower($urlParams[$i]);
                $res['ctrlr'] = $ctrlr_namespace_root.str_replace('\\', DIRECTORY_SEPARATOR, $current_dir).ucfirst(strtolower($urlParams[$i])).'Controller';
                $res['path'] = $ctrlr_file_fullpath;

                if (isset($urlParams[$i + self::URL_METHOD_OFFSET])) {
                    $res['actmd'] = $this->getControllerMethod($urlParams[$i + self::URL_METHOD_OFFSET], $res['ctrlr']);
                    $res['param'] = $this->getControllerParams($urlParams[$i + self::URL_METHOD_OFFSET], $res['ctrlr'], $i, $urlParams);
                } else {
                    $res['actmd'] = 'indexAction';
                }
                break;
            } else {
                // Dig current directory
                $current_dir .= strtolower($urlParams[$i]).'/';
            }
        }

        return $res;
    }

    /**
     * get Login Controller Setting
     *
     * @param string $auth_controller   : Login controller Name
     * @param string $auth_user_model   : Login user model
     * @param string $app_dir           : App dir name (ex. artichoke/$app_dir)
     * @param string $requested_params: parameter for Login Controller
     *
     * @return array : Controller parameters
     */
    public function getLoginController(
        string $auth_controller,
        string $auth_user_model,
        string $app_dir,
        string $requested_params
    ): array {
        $res = $this->InitControllerParams();

        if ((isset($auth_controller)) && ($auth_controller !== '')) {
            $auth_controller_in = $auth_controller;
            $auth_page_in = str_replace('controller', '', strtolower($auth_controller));
        } else {
            $auth_controller_in = 'LoginController';
            $auth_page_in = 'login';
        }

        $get_namespace = new GetNameSpace();
        if ((isset($auth_user_model)) && ($auth_user_model !== '')) {
            $auth_user_model_in = $auth_user_model;
        } else {
            $auth_user_model_in = $get_namespace->getFrameworkModelsStaticdbUserModel();
        }

        $res['ctrlr'] = $get_namespace->getAppController($app_dir).$auth_controller_in;
        $res['pages'] = $auth_page_in;
        $res['actmd'] = 'indexAction';
        $res['param'][0] = $requested_params;
        $res['param'][1] = $auth_user_model_in;

        return $res;
    }

    /**
     * get Exception Controller
     *
     * @param array  $actionArgs : parameters for ExceptionController
     * @param string $root_dir   : MMS root
     *
     * @return array : Controller parameters
     */
    public function getExceptionController(
        array $actionArgs,
        string $root_dir
    ): array {
        $res = $this->InitControllerParams();

        $ctrlr_file_fullpath = (new GetPaths())->getFrameworkControllerPath($root_dir).'ExceptionController.php';
        require_once $ctrlr_file_fullpath;

        $res['ctrlr'] = (new GetNameSpace())->getFrameworkController().'ExceptionController';
        $res['pages'] = 'exception';
        $res['actmd'] = 'indexAction';
        $res['param'] = $actionArgs;

        return $res;
    }

    /**
     * Select Controller Name
     *
     * @param string $controller_name : Controller name (ex. [$controller_name]Controller)
     * @param string $app_dir         : App dir name (ex. artichoke/$app_dir)
     *
     * @return string : Controller name space
     */
    public function selectController(string $controller_name, string $app_dir): string
    {
        return (new GetNameSpace())->getAppController($app_dir).ucfirst($controller_name).'Controller';
    }

    /**
     * get Controller Method
     *
     * @param string $method_name : Controller method name (ex. [$method_name]Action)
     * @param string $ctr_name    : Controller name (ex. \\artichoke\\framework\\controller\\[Hoge]Controller)
     * @param int    $index       : Controller name index
     * @param array  $urlParams   : URL paramters
     *
     * @return string : controller parameters
     */
    public function getControllerParams(string $method_name, string $ctr_name, int $index, array $urlParams): array
    {
        $res = array();
        $action_method = $method_name.'Action';
        if (($method_name !== '') && method_exists($ctr_name, $action_method)) {
            if (isset($urlParams[$index + self::URL_PARAMETER_OFFSET])) {
                $res = array_slice($urlParams, $index + self::URL_PARAMETER_OFFSET);
            }
        } else {
            $res = array_slice($urlParams, $index + self::URL_METHOD_OFFSET);
        }

        return $res;
    }

    /**
     * get Controller method
     *
     * @param string $method_name : Controller method name (ex. [$method_name]Action)
     * @param string $ctr_name    : Controller name (ex. \\artichoke\\framework\\controller\\[Hoge]Controller)
     *
     * @return string : Controller method name
     */
    public function getControllerMethod(string $method_name, $ctr_name): string
    {
        $action_method = $method_name.'Action';
        if (($method_name !== '') && method_exists($ctr_name, $action_method)) {
            return $action_method;
        } else {
            return 'indexAction';
        }
    }

    /**
     * Access Controller method
     *
     * @param array $args    : $args[0] = 'api' or unique name
     *                       $args[1] = 'post' or 'get'
     *                       $args[2] = request ID (like 43b01a75f3..)
     * @param array $app_dir : App dir name (ex. artichoke/$app_dir)
     *
     * @return string : Controller method name space
     */
    public function AccessControllerMethod(array $args, string $app_dir)
    {
        // Reading Configuration
        $apiClass = $this->selectController($args[0], $app_dir);

        $api = new $apiClass();

        if (isset($args[1])) {
            $method = $this->getControllerMethod($args[1], $api);
        }

        if (method_exists($api, $method)) {
            array_splice($args, 0, 2);
            $api->$method($args);
        } else {
            array_splice($args, 0, 1);
            $api->indexAction($args);
        }
    }

    /**
     * Generate Controller
     *
     * @param array $controller : Controller parameters
     */
    public function generateController(array $controller)
    {
        $ctrlrInstance = new $controller['ctrlr']($controller['pages']);
        $ctrlrInstance->{$controller['actmd']}($controller['param']);
    }
}
