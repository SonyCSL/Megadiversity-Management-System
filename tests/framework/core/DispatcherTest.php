<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\core\Dispatcher;
use artichoke\framework\core\Server;
use artichoke\framework\core\Configurator;

use artichoke\framework\util\GetPaths;

require_once dirname(dirname(__DIR__)).'/common/GenRootDir.php';

class DispatcherTest extends TestCase
{
    public function setUp()
    {
        $_SERVER['SERVER_NAME'] = 'server';
        $_SERVER['SERVER_PROTOCOL'] = 'protocol';

        $root = (new GenRootDir())->gen_root_dir();
        $force_path = array('framework' => $root.'/tests/testparam2/Seed.ini');
        $config = (new Configurator())->initialize($root, 'artichoke', $force_path);
    }

    // test for urlLoadFavicon
    /**
     * @runInSeparateProcess
     */
    public function test_urlLoadFavicon_LoadFaviconSuccess()
    {
        $dis = new Dispatcher();

        $urlParam = ['favicon.ico', 'favicon.gif', 'favicon.png'];

        $ref = new ReflectionClass(get_class($dis));
        $test_method = $ref->getMethod('urlLoadFavicon');

        $test_method->setAccessible(true);

        $getPath = new GetPaths();
        $server = new Server($_SERVER);
        foreach ($urlParam as $param) {
            $args = array(
                $param,
                '',
                '',
                $getPath,
                $server,
            );
            $out = $test_method->invokeArgs(null, $args);

            $this->assertTrue($out);
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function test_urlLoadFavicon_LoadFaviconError()
    {
        $dis = new Dispatcher();

        $urlParam = ['favicon.ica', 'favicon.gifa', 'favicon.pn'];

        $ref = new ReflectionClass(get_class($dis));
        $test_method = $ref->getMethod('urlLoadFavicon');

        $test_method->setAccessible(true);

        $getPath = new GetPaths();
        $server = new Server($_SERVER);
        foreach ($urlParam as $param) {
            $args = array(
                $param,
                '',
                '',
                $getPath,
                $server,
            );
            $out = $test_method->invokeArgs(null, $args);

            $this->assertFalse($out);
        }
    }

    // test for urlApiAccess
    /**
     * @runInSeparateProcess
     */
    public function test_urlApiAccess_ApiAccessSuccess()
    {
        $dis = new Dispatcher();

        $ref = new ReflectionClass(get_class($dis));
        $test_method = $ref->getMethod('urlApiAccess');

        $test_method->setAccessible(true);

        $config = array('api_available' => true);

        $args = array(
            $config,
            ['File', 'image'],
            'app',
            'framework',
        );
        $out = $test_method->invokeArgs(null, $args);

        $this->assertTrue($out);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_urlApiAccess_ApiAccessFail()
    {
        $dis = new Dispatcher();

        $ref = new ReflectionClass(get_class($dis));
        $test_method = $ref->getMethod('urlApiAccess');

        $test_method->setAccessible(true);

        $config = array('api_available' => true);

        $args = array(
            $config,
            ['File', 'image'],
            '',
            'framework',
        );
        $out = $test_method->invokeArgs(null, $args);

        $this->assertFalse($out);
    }

    // test for urlException
    /**
     * @runInSeparateProcess
     */
    public function test_urlException_ExceptionFalse()
    {
        $dis = new Dispatcher();

        $ref = new ReflectionClass(get_class($dis));
        $test_method = $ref->getMethod('urlException');

        $test_method->setAccessible(true);

        $config = array('api_available' => true);

        $server = new Server($_SERVER);
        $args = array(
            $server,
            $config,
            ['exception1'],
            'app',
            'framework',
        );
        $out = $test_method->invokeArgs(null, $args);

        $this->assertFalse($out);
    }

    // test for urlApiController
    /**
     * @runInSeparateProcess
     */
    public function test_urlApiController_ControllerNameExistsApiAccessSuccess()
    {
        $dis = new Dispatcher();

        $ref = new ReflectionClass(get_class($dis));
        $test_method = $ref->getMethod('urlApiController');

        $test_method->setAccessible(true);

        $config = array(
            'api_available' => false,
            'api_controller' => 'file',
        );

        $args = array(
            $config,
            ['file'],
            'app',
            'framework',
        );
        $out = $test_method->invokeArgs(null, $args);

        $this->assertFalse($out);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_urlApiController_ControllerNameNotExistsApiAccessSuccess()
    {
        $dis = new Dispatcher();

        $ref = new ReflectionClass(get_class($dis));
        $test_method = $ref->getMethod('urlApiController');

        $test_method->setAccessible(true);

        $config = array(
            'api_available' => false,
        );

        $args = array(
            $config,
            ['file'],
            'app',
            'framework',
        );
        $out = $test_method->invokeArgs(null, $args);

        $this->assertFalse($out);
    }

    // test for urlCheckErrorCase
    /**
     * @runInSeparateProcess
     */
    public function test_urlCheckErrorCase_UrlParamIsFile()
    {
        $dis = new Dispatcher();

        $ref = new ReflectionClass(get_class($dis));
        $test_method = $ref->getMethod('urlCheckErrorCase');

        $test_method->setAccessible(true);

        $server = new Server($_SERVER);

        $args = array(
            'file',
            true,
            false,
            $server,
        );
        $out = $test_method->invokeArgs(null, $args);

        $this->assertTrue($out);

        $args = array(
            'file',
            false,
            false,
            $server,
        );
        $out = $test_method->invokeArgs(null, $args);

        $this->assertFalse($out);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_urlCheckErrorCase_UrlParamIsNotFile()
    {
        $dis = new Dispatcher();

        $ref = new ReflectionClass(get_class($dis));
        $test_method = $ref->getMethod('urlCheckErrorCase');

        $test_method->setAccessible(true);

        $server = new Server($_SERVER);

        $urlParam = ['protected', 'public', 'template'];

        foreach ($urlParam as $param) {
            $args = array(
                $param,
                true,
                true,
                $server,
            );
            $out = $test_method->invokeArgs(null, $args);

            $this->assertTrue($out);
        }

        $urlParam = ['protectd', 'publc', 'templte'];

        foreach ($urlParam as $param) {
            $args = array(
                $param,
                true,
                true,
                $server,
            );
            $out = $test_method->invokeArgs(null, $args);

            $this->assertFalse($out);
        }
    }

    // test for LoadStaticResources
    /**
     * @runInSeparateProcess
     */
    public function test_LoadStaticResources()
    {
        $dis = new Dispatcher();

        $ref = new ReflectionClass(get_class($dis));
        $test_method = $ref->getMethod('LoadStaticResources');

        $test_method->setAccessible(true);

        $getPath = new GetPaths();
        $server = new Server($_SERVER);

        $args = array(
            'root',
            'app',
            '',
            true,
            $getPath,
            $server,
        );
        $out = $test_method->invokeArgs(null, $args);

        $this->assertTrue($out);
    }

    // test for UrlLoadScripts
    /**
     * @runInSeparateProcess
     */
    public function test_LoadScripts_UrlParamIsCSSorJS()
    {
        $dis = new Dispatcher();

        $ref = new ReflectionClass(get_class($dis));
        $test_method = $ref->getMethod('UrlLoadScripts');

        $test_method->setAccessible(true);

        $getPath = new GetPaths();
        $server = new Server($_SERVER);

        $urlParam = ['css', 'js'];

        foreach ($urlParam as $param) {
            $args = array(
                $param,
                'root',
                'app',
                '',
                true,
                $getPath,
                $server,
            );
            $out = $test_method->invokeArgs(null, $args);

            $this->assertTrue($out);
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function test_LoadScripts_UrlParamIsNotCSSorJS()
    {
        $dis = new Dispatcher();

        $ref = new ReflectionClass(get_class($dis));
        $test_method = $ref->getMethod('UrlLoadScripts');

        $test_method->setAccessible(true);

        $getPath = new GetPaths();
        $server = new Server($_SERVER);

        $args = array(
            'html',
            'root',
            'app',
            '',
            true,
            $getPath,
            $server,
        );
        $out = $test_method->invokeArgs(null, $args);

        $this->assertFalse($out);
    }
}
