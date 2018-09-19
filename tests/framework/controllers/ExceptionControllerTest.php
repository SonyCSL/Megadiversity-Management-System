<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\controllers\ExceptionController;

use artichoke\framework\core\Configurator;

require_once dirname(dirname(__DIR__)).'/common/GenRootDir.php';

class ExceptionControllerTest extends TestCase
{
    public function setUp()
    {
        $root = (new GenRootDir())->gen_root_dir();
        $force_path = array('framework' => $root.'/tests/testparam2/Seed.ini');
        $config = (new Configurator())->initialize($root, 'artichoke', $force_path);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function initialize()
    {
        $_SERVER['SERVER_NAME'] = 'server';
    }

    // test for indexAction
    /**
     * @runInSeparateProcess
     */
    public function test_indexAction_Args5()
    {
        $this->initialize();
        $exception = new ExceptionController();

        $exception->indexAction(['test0', 'test1', 'test2', 'test3', 'test4']);

        $res = $exception->getAllParams()['pageVariables'];
        $this->assertEquals($res['detail'], 'test1');
        $this->assertEquals($res['exCode'], 'test0');
        $this->assertEquals($res['params'], 'test4');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_indexAction_Args6()
    {
        $this->initialize();
        $exception = new ExceptionController();

        $_SERVER['SERVER_PROTOCOL'] = 'protocol';
        $exception->indexAction(['test0', 'test1', 'test2', 'test3', 'test4', 404]);

        $res = $exception->getAllParams()['pageVariables'];
        $this->assertEquals($res['detail'], 'test1');
        $this->assertEquals($res['exCode'], 'test0');
        $this->assertEquals($res['params'], 'test4');

        $ref = xdebug_get_headers();

        $this->assertEquals($ref[0], 'protocol 404 Not Found');
    }
}
