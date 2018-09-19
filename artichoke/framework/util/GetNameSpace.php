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

class GetNameSpace
{
    /**
     * Get app name space
     *
     * @param string $app_dir : App dir name
     *
     * @return string : \\root_dir\\app
     */
    public function getApp(string $app_dir): string
    {
        return '\\artichoke\\'.$app_dir;
    }

    /**
     * Get app/controller name space
     *
     * @param string $app_dir : App dir name
     *
     * @return string : \\root_dir\\app\\controllers
     */
    public function getAppController(string $app_dir): string
    {
        return $this->getApp($app_dir).'\\controllers\\';
    }

    /**
     * Get framework/Controller name space
     *
     * @param string $app_dir : App dir name
     *
     * @return string : \\root_dir\\app\\controllers
     */
    public function getFrameworkController(): string
    {
        return $this->getAppController('framework');
    }

    /**
     * Get framework/models/staticdb/UserModel name space
     *
     * @param string $app_dir : App dir name
     *
     * @return string : \\root_dir\\app\\controllers
     */
    public function getFrameworkModelsStaticdbUserModel(): string
    {
        return $this->getApp('framework').'\\models\\client\\User';
    }

    /**
     * Get artichoke\framework\controllers\ExceptionController
     *
     * @return string : artichoke\framework\controllers\ExceptionController
     */
    public function getNameSpaceToExceptionController(): string
    {
        return 'artichoke\framework\controllers\ExceptionController';
    }

    /**
     * Get $app_dir/models/analytics/$model_class/Models name space
     *
     * @param string $app_dir     : App dir name
     * @param string $model_class : model class dir name
     *
     * @return string : artichoke\\$app_dir\\models\\Analytics\\$model_class\\model
     */
    public function getModelsAnalyticsModel(string $app_dir, string $model_class): string
    {
        return $this->getApp($app_dir).'\\models\\analytics\\'.$model_class.'Model';
    }

    /**
     * Get framework/models/analytics/$model_class/Models name space
     *
     * @param string $model_class : model class dir name
     *
     * @return string : artichoke\\framework\\models\\Analytics\\$model_class\\model
     */
    public function getFrameworkModelsAnalyticsModel(string $model_class): string
    {
        return $this->getModelsAnalyticsModel('framework', $model_class);
    }
}
