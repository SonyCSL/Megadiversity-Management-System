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

namespace artichoke\framework\core;

use artichoke\framework\util\GetPaths;

use artichoke\framework\util\LoadResource;
use artichoke\framework\util\AccessControllerApi;

final class Dispatcher
{
    public static function run(Configurator $configurator, Server $server, $app = null)
    {
        // -- Session --
        (new Session())->init();
        $loggedIn = (new Session())->loginStatus();

        $root_dir = $configurator->read('system_root');
        $app_dir = $configurator->read('app_dir');
        $config = $configurator->read('config');

        if ($app_dir === null) {
            $app_dir = '';
        }

        if ($config === null) {
            $config = [];
        }

        // Auth settings
        $require_auth = (isset($config['user_auth']) && $config['user_auth'] === true);
        /*
        $require_auth & $loggedIn :
                    │ T & T │ T & F │ F & ~ │
        (controller)├───────┼───────┼───────┤
        Normal      │ true  │ false │ true  │
        API         │ true  │ true  │ true  │
        (View file) ├───────┼───────┼───────┤
        template    │ false │ false │ false │
        protected   │ true  │ false │ true  │
        public      │ true  │ true  │ true  │
        framework   │ true  │ true  │ true  │
        */

        // array for controller
        $get_paths = new GetPaths();

        // Get all parameters
        $request_uri = $server->getRequestURI();
        $requested_params = $get_paths->getRequestedPaths($request_uri);
        $urlParams = $get_paths->splitUri($requested_params);

        // Request cancel and Load resources
        // ex. picture file from internal filesystem

        //Select Process from URL
        $isLoadAddProtectedPath = ($require_auth && $loggedIn) || !$require_auth;

        if (self::urlLoadFavicon($urlParams[0], $root_dir, $app_dir, $get_paths, $server)) {
            exit;
        }
        if (self::urlException($server, $config, $urlParams, $app, $app_dir)) {
            exit;
        }
        if (self::urlApiController($config, $urlParams, $app, $app_dir)) {
            exit;
        }
        if (self::urlLoadScripts($urlParams[0], $root_dir, $app_dir, $requested_params, $isLoadAddProtectedPath, $get_paths, $server)) {
            exit;
        }
        if (self::urlCheckErrorCase($urlParams[0], $require_auth, $loggedIn, $server)) {
            exit;
        }

        // #################### BEFORE LOGIN ############################################################
        $controller = new AccessControllerApi();
        if ($require_auth && !$loggedIn) {
            if ($server->fromAjax()) {
                $server->sendHttpStatusCode(404);
                exit;
            }
            $dispatch = $controller->getLoginController($config['auth_controller'], $config['auth_user_model'], $app_dir, $requested_params);

            $controller->generateController($dispatch);
            exit;
        }
        // #################### AFTER LOGIN ############################################################
        $dispatch = $controller->searchControllerFromURL($urlParams, empty($app), $root_dir, $app_dir);
        if (!empty($dispatch['path'])) {
            require_once $dispatch['path'];
        } else {
            if (self::LoadStaticResources($root_dir, $app_dir, $requested_params, $isLoadAddProtectedPath, $get_paths, $server)) {
                exit;
            }
        }

        // If directly accessing .php file
        // Redirect Exception page
        if (substr($request_uri, strrpos($request_uri, '.') + 1) === 'php') {
            $expMes = [3, 'Direct accessing to .php file is forbidden', $dispatch['ctrlr'], $dispatch['actmd'], '/'.$requested_params, 403];
            $dispatch = $controller->getExceptionController($expMes, $root_dir);

            //Throw exception
            $controller->generateController($dispatch);
            exit;
        }

        // Generate
        $controller->generateController($dispatch);
        exit;
    }

    private static function urlLoadFavicon(
        string $urlParam,
        string $root_dir,
        string $app_dir,
        GetPaths $get_paths,
        Server $server
    ): bool {
        if ($urlParam === 'favicon.ico' ||
            $urlParam === 'favicon.gif' ||
            $urlParam === 'favicon.png'
        ) {
            $path[0] = $get_paths->getAppPublicPath($root_dir, $app_dir).$urlParam;
            $path[1] = $get_paths->getFrameworkResourcePath($root_dir).$urlParam;

            (new LoadResource())->loadFileFromArrayAndExit($path, $server);
            return true;
        }
        return false;
    }

    private static function urlApiAccess(
        array $config,
        array $urlParams,
        string $app,
        string $app_dir
    ): bool {
        if (!empty($app) && isset($config['api_available']) && ($config['api_available'] === true)) {
            (new AccessControllerApi())->AccessControllerMethod($urlParams, $app_dir);
            return true;
        }

        return false;
    }

    private static function urlException(
        Server $server,
        array $config,
        array $urlParams,
        string $app,
        string $app_dir
    ): bool {
        if ($urlParams[0] === 'exception') {
            $server->redirect();
            return self::urlApiAccess($config, $urlParams, $app, $app_dir);
        }

        return false;
    }

    private static function urlApiController(
        array $config,
        array $urlParams,
        string $app,
        string $app_dir
    ): bool {
        // check API controller name (unique or default controller)
        if (!empty($config['api_controller'])) {
            $api_controller_name = str_replace('controller', '', strtolower($config['api_controller']));
        } else {
            // Default is "ApiController"
            $api_controller_name = 'api';
        }

        if ($urlParams[0] === $api_controller_name) {
            return self::urlApiAccess($config, $urlParams, $app, $app_dir);
        }

        return false;
    }

    private static function LoadStaticResources(
        string $root_dir,
        string $app_dir,
        string $requested_params,
        bool $isLoadAddProtectedPath,
        GetPaths $get_paths,
        Server $server
    ): bool {
        $path[0] = $get_paths->getAppPublicPath($root_dir, $app_dir).$requested_params;
        $path[1] = $get_paths->getFrameworkViewPath($root_dir).'/'.$requested_params;
        if ($isLoadAddProtectedPath) {
            array_unshift($path, $get_paths->getAppProtectedPath($root_dir, $app_dir).$requested_params);
        }

        (new LoadResource())->loadFileFromArrayAndExit($path, $server);
        return true;
    }

    private static function urlLoadScripts(
        string $urlParam,
        string $root_dir,
        string $app_dir,
        string $requested_params,
        bool $isLoadAddProtectedPath,
        GetPaths $get_paths,
        Server $server
    ): bool {
        if (($urlParam === 'css') || ($urlParam === 'js')) {
            return self::LoadStaticResources($root_dir, $app_dir, $requested_params, $isLoadAddProtectedPath, $get_paths, $server);
        }

        return false;
    }

    private static function urlCheckErrorCase(string $urlParam, bool $require_auth, bool $loggedIn, Server $server): bool
    {
        if ($urlParam === 'file') { // FileController ./file/thumbnail/b41d9fa6c5...
            if ($require_auth && !$loggedIn) {
                $server->sendHttpStatusCode(404);
                return true;
            }
        } elseif ( //Forbidden
                $urlParam === 'protected' ||
                $urlParam === 'public' ||
                $urlParam === 'template'
        ) {
            $server->sendHttpStatusCode(403);
            return true;
        }

        return false;
    }
}
