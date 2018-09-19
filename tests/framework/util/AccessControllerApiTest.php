<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\util\AccessControllerApi;

class AccessControllerApiTest extends TestCase
{
    private $root;
    const FRAMEWORK_CONTROLLER_PATH = '\artichoke\framework\controllers';

    private const EMPTY_CONTROLLER_PARAM = array(
        'ctrlr' => '',
        'actmd' => '',
        'pages' => '',
        'param' => [],
        'path' => '',
    );

    /**
     * @doesNotPerformAssertions
     */
    private function controllerParamsMatcher(array $test, string $ctrlr, string $actmd, string $pages, array $param, string $path = null)
    {
        $this->assertEquals($test['ctrlr'], $ctrlr);
        $this->assertEquals($test['actmd'], $actmd);
        $this->assertEquals($test['pages'], $pages);
        $this->assertEquals($test['param'], $param);

        if ($path !== null) {
            $this->assertEquals($test['path'], $path);
        }
    }

    /**
     * @before
     */
    public function gen_root_dir()
    {
        $current_dir = __DIR__;
        $divided_dir = explode('/', $current_dir);

        $this->root = '';
        foreach ($divided_dir as $val) {
            if ($val !== '') {
                $this->root = $this->root.'/'.$val;

                if ($val === 'syneco-cms') {
                    break;
                }
            }
        }

        $this->root = $this->root.'/';
    }

    //Test for InitControllerParams
    public function test_InitControllerParams()
    {
        $res = (new AccessControllerApi())->InitControllerParams();

        $this->assertEquals($res, self::EMPTY_CONTROLLER_PARAM);
    }

    //Test for searchControllerFromURL
    public function test_searchControllerFromURL_UrlParamExistCaseFrameworkMethodExists()
    {
        $urlParams = array('Apitester', 'run', 'hoge', 'piyo', 'hogehoge');

        $res = (new AccessControllerApi())->searchControllerFromURL($urlParams, true, $this->root, 'framework');

        $this->controllerParamsMatcher(
            $res,
            self::FRAMEWORK_CONTROLLER_PATH.'\ApitesterController',
            'runAction',
            'apitester',
            array('hoge', 'piyo', 'hogehoge'),
            $this->root.'/artichoke/framework/controllers/ApitesterController.php'
        );
    }

    public function test_searchControllerFromURL_UrlParamNull()
    {
        $urlParams = array();

        $res = (new AccessControllerApi())->searchControllerFromURL($urlParams, true, $this->root, 'framework');

        $this->assertEquals($res, self::EMPTY_CONTROLLER_PARAM);
    }

    public function test_searchControllerFromURL_UrlParamExistCaseFrameworkNoMethodAndPameter()
    {
        $urlParams = array('api');

        $res = (new AccessControllerApi())->searchControllerFromURL($urlParams, true, $this->root, 'framework');

        $this->controllerParamsMatcher(
            $res,
            self::FRAMEWORK_CONTROLLER_PATH.'\ApiController',
            'indexAction',
            'api',
            array(),
            $this->root.'/artichoke/framework/controllers/ApiController.php'
        );
    }

    public function test_searchControllerFromURL_UrlParamExistCaseFrameworkNoMethod2Pameter()
    {
        $urlParams = array('inDEx', 'hoge', 'piyo');

        $res = (new AccessControllerApi())->searchControllerFromURL($urlParams, true, $this->root, 'framework');

        $this->controllerParamsMatcher(
            $res,
            self::FRAMEWORK_CONTROLLER_PATH.'\IndexController',
            'indexAction',
            'index',
            array('hoge', 'piyo'),
            $this->root.'/artichoke/framework/controllers/IndexController.php'
        );
    }

    public function test_searchControllerFromURL_UrlParamExistCaseApp()
    {
        $urlParams = array('Exception');

        $res = (new AccessControllerApi())->searchControllerFromURL($urlParams, false, $this->root, 'core');

        $this->assertEquals($res, self::EMPTY_CONTROLLER_PARAM);
    }

    //Test for getLoginController
    public function test_getLoginController_AuthControllerAndAuthUserModelExists()
    {
        $res = (new AccessControllerApi())->getLoginController('hoge', 'piyo', 'framework', 'param');

        $this->controllerParamsMatcher(
            $res,
            self::FRAMEWORK_CONTROLLER_PATH.'\hoge',
            'indexAction',
            'hoge',
            array('param', 'piyo')
        );
    }

    public function test_getLoginController_AuthControllerNullAuthUserModelExists()
    {
        $res = (new AccessControllerApi())->getLoginController('', 'piyo', 'framework', 'param');

        $this->controllerParamsMatcher(
            $res,
            self::FRAMEWORK_CONTROLLER_PATH.'\LoginController',
            'indexAction',
            'login',
            array('param', 'piyo')
        );
    }

    public function test_getLoginController_AuthControllerExistsAuthUserModelNull()
    {
        $res = (new AccessControllerApi())->getLoginController('hoge', '', 'framework', 'param');

        $this->controllerParamsMatcher(
            $res,
            self::FRAMEWORK_CONTROLLER_PATH.'\hoge',
            'indexAction',
            'hoge',
            array('param', '\artichoke\framework\models\client\User')
        );
    }

    //Test for getExecptionController
    public function test_getExceptionController_UseCase()
    {
        $args = array('hoge', 'piyo');
        $res = (new AccessControllerApi())->getExceptionController($args, $this->root);

        $this->controllerParamsMatcher(
            $res,
            self::FRAMEWORK_CONTROLLER_PATH.'\ExceptionController',
            'indexAction',
            'exception',
            $args
        );
    }

    public function test_getExceptionController_Null()
    {
        $args = array('', '');
        $res = (new AccessControllerApi())->getExceptionController($args, $this->root);

        $this->controllerParamsMatcher(
            $res,
            self::FRAMEWORK_CONTROLLER_PATH.'\ExceptionController',
            'indexAction',
            'exception',
            $args
        );
    }

    //Test for selectController
    public function test_selectController_Usecase()
    {
        $res = (new AccessControllerApi())->selectController('hoge', 'framework');

        $this->assertEquals($res, self::FRAMEWORK_CONTROLLER_PATH.'\HogeController');
    }
}
