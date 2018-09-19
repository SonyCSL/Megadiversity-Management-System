<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\controllers\IndexController;
use artichoke\framework\core\Configurator;

use artichoke\framework\models\application\AppbuilderModel;

require_once dirname(dirname(__DIR__)).'/common/GenRootDir.php';

class IndexControllerTest extends TestCase
{
    private $root = '';

    public function setUp()
    {
        $this->root = (new GenRootDir())->gen_root_dir();
        $force_path = array('framework' => $this->root.'/tests/testparam2/Seed.ini');
        $config = (new Configurator())->initialize($this->root, 'artichoke', $force_path);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function initialize()
    {
        $_SERVER['SERVER_NAME'] = 'server';
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_exec($testClass)
    {
        $setter = function (AppbuilderModel $build) {
            return $this->exec($build);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    // Test for indexAction
    /**
     * @runInSeparateProcess
     */
    public function test_exec_AppBuildEmpty()
    {
        $this->initialize();
        $_REQUEST['app_name'] = 'app_name';

        $indexController = new IndexController();

        $indexController->indexAction(['exec']);

        $res = $indexController->getAllParams()['pageVariables'];

        $this->assertEquals($res, ['failedMes' => 'Please set your host.domain name']);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_exec_MkdirFalse()
    {
        $this->initialize();

        $_REQUEST = array(
            'app_name' => 'app_name',
            'app_fqdn' => 'app_fqdn',
        );

        $indexController = new IndexController();

        $observer = $this->getMockBuilder(AppbuilderModel::class)
                         ->setMethods(['mkdir'])
                         ->getMock();

        $observer->expects($this->once())
                         ->method('mkdir')
                         ->willReturn(false);

        $out = $this->bound_exec($indexController)($observer);

        $this->assertEquals($out, 'Permission denied: couldn\'t create any application directory.');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_exec_MkfilesFail()
    {
        $this->initialize();

        $_REQUEST = array(
            'app_name' => 'app_name',
            'app_fqdn' => 'app_fqdn',
        );

        $indexController = new IndexController();

        $observer = $this->getMockBuilder(AppbuilderModel::class)
                         ->setMethods(['mkdir', 'mkfiles'])
                         ->getMock();

        $observer->expects($this->once())
                         ->method('mkdir')
                         ->willReturn(true);

        $observer->expects($this->once())
                         ->method('mkfiles')
                         ->willReturn([false, 'create file failed']);

        $out = $this->bound_exec($indexController)($observer);

        $this->assertEquals($out, 'create file failed');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_exec_AllOK()
    {
        $this->initialize();

        $_REQUEST = array(
            'app_name' => 'app_name',
            'app_fqdn' => 'app_fqdn',
        );

        $indexController = new IndexController();

        $observer = $this->getMockBuilder(AppbuilderModel::class)
                         ->setMethods(['mkdir', 'mkfiles'])
                         ->getMock();

        $observer->expects($this->once())
                         ->method('mkdir')
                         ->willReturn(true);

        $observer->expects($this->once())
                         ->method('mkfiles')
                         ->willReturn([true]);

        $out = $this->bound_exec($indexController)($observer);

        $this->assertEquals($out, '');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_indexAction_ResultNotEmpty()
    {
        $this->initialize();

        $_REQUEST = array(
            'app_name' => 'app_name',
            'app_fqdn' => 'app_fqdn',
        );

        $indexController = new IndexController();
        $out = $indexController->indexAction(['exec']);

        if (is_dir($this->root.'/artichoke/app_name')) {
            exec('rm -rf '.$this->root.'/artichoke/app_name');
        }

        $this->assertNull($out);
    }
}
