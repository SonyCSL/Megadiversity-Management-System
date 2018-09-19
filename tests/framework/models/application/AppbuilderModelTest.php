<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\core\Configurator;
use artichoke\framework\models\application\AppbuilderModel;

class AppbuilderModelTest extends TestCase
{
    const NEW_APP_NAME = 'testnewapp';
    const NEW_APP_FQDN = 'testapp.org';

    /**
     * @doesNotPerformAssertions
     */
    public function cleanUpDir()
    {
        exec('rm -Rf '.__DIR__.'/artichoke/'.self::NEW_APP_NAME);
    }

    public function tearDown()
    {
        $this->cleanUpDir();
    }

    /**
     * @runInSeparateProcess
     */
    public function test_setConf()
    {
        $_SERVER['SERVER_NAME'] = 'mytesthost';
        $configurator = new Configurator();
        $configurator->initialize(__DIR__, 'artichoke');

        $this->assertEquals([
            false,
            'Please set your application name',
        ], (new AppbuilderModel())->setConf([]));
        $this->assertEquals([
            false,
            'Please set your host.domain name',
        ], (new AppbuilderModel())->setConf([
            'app_name' => 'test',
        ]));
        $this->assertEquals([
            false,
            'Couldn\'t use this application name (forbidden word)',
        ], (new AppbuilderModel())->setConf([
            'app_name' => 'fRamewORk',
            'app_fqdn' => 'test.mydomain',
        ]));

        $this->assertEquals([
            false,
            'Couldn\'t use this application name (already exists)',
        ], (new AppbuilderModel())->setConf([
            'app_name' => 'testapp1',
            'app_fqdn' => 'testapp.ne.jp',
        ]));
        $this->assertEquals([
            false,
            'Couldn\'t use this FQDN (already exists)',
        ], (new AppbuilderModel())->setConf([
            'app_name' => 'testapp3',
            'app_fqdn' => 'testapp.co.jp',
        ]));

        $this->assertEquals([
            true,
            '',
        ], (new AppbuilderModel())->setConf([
            'app_name' => self::NEW_APP_NAME,
            'app_fqdn' => self::NEW_APP_FQDN,
        ]));
    }

    /**
     * @runInSeparateProcess
     */
    public function test_build_success()
    {
        $_SERVER['SERVER_NAME'] = 'mytesthost';
        $configurator = new Configurator();
        $configurator->initialize(__DIR__, 'artichoke');

        $this->cleanUpDir();
        $ab = new AppbuilderModel();
        $ab->setConf([
            'app_name' => self::NEW_APP_NAME,
            'app_fqdn' => self::NEW_APP_FQDN,
        ]);
        $this->assertTrue($ab->mkdir());
        $result = $ab->mkfiles();
        $this->assertTrue($result[0]);

        $this->assertFileIsReadable(__DIR__.'/artichoke/'.self::NEW_APP_NAME.'/Seed.ini');
        $this->assertFileIsReadable(__DIR__.'/artichoke/'.self::NEW_APP_NAME.'/views/template/index.html');
        $this->assertFileIsReadable(__DIR__.'/artichoke/'.self::NEW_APP_NAME.'/views/protected/resources/1.png');
        $this->assertFileIsReadable(__DIR__.'/artichoke/'.self::NEW_APP_NAME.'/views/protected/resources/2.png');
        $this->assertFileIsReadable(__DIR__.'/artichoke/'.self::NEW_APP_NAME.'/controllers/IndexController.php');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_build_fail_mkdir()
    {
        $_SERVER['SERVER_NAME'] = 'mytesthost';
        $configurator = new Configurator();
        $configurator->initialize(__DIR__, 'artichoke');

        $this->cleanUpDir();
        $ab = new AppbuilderModel();
        $this->assertFalse($ab->mkdir());
    }
}
