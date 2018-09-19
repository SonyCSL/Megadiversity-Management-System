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

class GetPaths
{
    /**
     * Get URI path
     *
     * @param string $uri : URI paramater ($_SERVER['REQUEST_URL'])
     *
     * @return string : URI Path
     */
    public function getRequestedPaths(string $uri): string
    {
        $requested = explode('?', $uri);
        return trim($requested[0], '/');
    }

    /**
     * Split URI path
     *
     * @param string $path : URI path
     *
     * @return string : Splited URI path
     */
    public function splitUri(string $path): array
    {
        $result = array();
        if ($path !== '') {
            $result = explode('/', $path);
        } else {
            $result = ['index'];
        }

        return $result;
    }

    /**
     * Get App path
     *
     * @param string $root_dir : MMS root dir
     * @param string $app_dir  : app root dir
     *
     * @return string : App/View path
     */
    public function getAppPath(string $root_dir, string $app_dir): string
    {
        return $root_dir.'/artichoke/'.$app_dir;
    }

    /**
     * Get App/View path
     *
     * @param string $root_dir : MMS root dir
     * @param string $app_dir  : app root dir
     *
     * @return string : App/View path
     */
    public function getAppViewPath(string $root_dir, string $app_dir): string
    {
        return $this->getAppPath($root_dir, $app_dir).'/views';
    }

    /**
     * Get framework/view path
     *
     * @param string $root_dir : MMS root dir
     *
     * @return string : framework/view path
     */
    public function getFrameworkViewPath(string $root_dir): string
    {
        return $this->getAppViewPath($root_dir, 'framework');
    }

    /**
     * Get framework/view path
     *
     * @param string $root_dir : MMS root dir
     * @param string $app_dir  : app root dir
     *
     * @return string : app/view/public path
     */
    public function getAppPublicPath(string $root_dir, string $app_dir): string
    {
        $res = '';
        $res = $this->getAppViewPath($root_dir, $app_dir);
        return $res.'/public/';
    }

    /**
     * Get framework/view path
     *
     * @param string $root_dir : MMS root dir
     * @param string $app_dir  : app root dir
     *
     * @return string : app/view/protected path
     */
    public function getAppProtectedPath(string $root_dir, string $app_dir): string
    {
        $res = '';
        $res = $this->getAppViewPath($root_dir, $app_dir);
        return $res.'/protected/';
    }

    /**
     * Get framework resource path
     *
     * @param string $root_dir : MMS root dir
     *
     * @return string : root/framework/resouces path
     */
    public function getFrameworkResourcePath(string $root_dir): string
    {
        return $this->getAppPath($root_dir, 'framework').'/resources/';
    }

    /**
     * Get app controller path
     *
     * @param string $root_dir : MMS root dir
     * @param string $app_dir  : app root dir
     *
     * @return string : app/controller path
     */
    public function getAppControllerPath(string $root_dir, string $app_dir): string
    {
        return $this->getAppPath($root_dir, $app_dir).'/controllers/';
    }

    /**
     * Get framework controller path
     *
     * @param string $root_dir : MMS root dir
     *
     * @return string : framework/controller path
     */
    public function getFrameworkControllerPath(string $root_dir): string
    {
        return $this->getAppControllerPath($root_dir, 'framework');
    }

    /**
     * Get template path
     *
     * @param string $upper_dir : $upper_dir/template/
     *
     * @return string : $upper_dir/template path
     */
    public function getTemplatePath(string $upper_dir): string
    {
        return $upper_dir.'/template/';
    }

    /**
     * search Render file from view
     *
     * @param string $view_root : directory path of view
     * @param string $file_name : render file name
     *
     * @return string : if file exits , return file path; not exists, return '';
     */
    public function searchRenderFile(string $view_root, string $file_name): string
    {
        $path = ['/template/', '/protected/', '/protected/css/', '/protected/js/', '/protected/resources/', '/public/', '/public/css/', '/public/js/', '/public/resources/'];
        foreach ($path as $mid_dir) {
            if (is_readable($view_root.$mid_dir.$file_name)) {
                return $view_root.$mid_dir.$file_name;
            }
        }

        return '';
    }

    /**
     * Get File path to exception.html
     *
     * @param string $root_dir : MMS root dir
     *
     * @return string : $root_dir/artichoke/framework/views/template/exception.html
     */
    public function getFilePathToExceptionHtml(string $root_dir): string
    {
        return $this->getFrameworkViewPath($root_dir).'/template/exception.html';
    }

    /**
     * Get file path to index.html
     *
     * @param string $upper_dir : upper dir of index.html
     *
     * @return string : $path.html
     */
    public function getFilePathToIndexHtml(string $upper_dir): string
    {
        return $upper_dir.'/index.html';
    }

    /**
     * Generate HTML file path
     *
     * @param string $path : path to HTML file
     *
     * @return string : $path.html
     */
    public function genHtmlFileName(string $path): string
    {
        return $path.'.html';
    }
}
