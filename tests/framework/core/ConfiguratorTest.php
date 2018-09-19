<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\core\Configurator;

class ConfiguratorTest extends TestCase
{
    const TEST_SEARCH_DIR = 'tests';

    const TEST_SERVER_NAME1 = 'testurl.com';
    const TEST_SERVER_NAME2 = 'hoge.org';

    const TEST_PARAM_DIR_NAME1 = 'testparam1';
    const TEST_PARAM_DIR_NAME2 = 'testparam2';

    const TEST_HTTP_HOST = 'http_host';

    private $root;

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

    // Test for load
    /**
     * @runInSeparateProcess
     */
    public function test_initialize_load_SeedExistAllParam()
    {
        $_SERVER = array(
            'SERVER_NAME' => self::TEST_SERVER_NAME1,
            'HTTP_HOST' => self::TEST_HTTP_HOST,
        );

        $configurator = new Configurator();
        $configurator->initialize($this->root, self::TEST_SEARCH_DIR);

        $this->assertEquals($configurator->read('system_root'), $this->root);
        $this->assertEquals($configurator->read('system_root'), $this->root);

        $this->assertEquals($configurator->app_list()[0]['app_name'], self::TEST_PARAM_DIR_NAME2);
        $this->assertEquals($configurator->app_list()[0]['app_fqdn'], self::TEST_SERVER_NAME2);

        $this->assertEquals($configurator->app_list()[1]['app_name'], self::TEST_PARAM_DIR_NAME1);
        $this->assertEquals($configurator->app_list()[1]['app_fqdn'], self::TEST_SERVER_NAME1);

        $path = $this->root.'/'.self::TEST_SEARCH_DIR.'/'.self::TEST_PARAM_DIR_NAME1.'/Seed.ini';

        $this->assertEquals($configurator->getSeed(),
                            parse_ini_file($path, true, INI_SCANNER_TYPED));
        $this->assertEquals($configurator->read('app_dir'), self::TEST_PARAM_DIR_NAME1);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_initialize_load_AppDefault()
    {
        $_SERVER = array(
            'SERVER_NAME' => self::TEST_SERVER_NAME2,
            'HTTP_HOST' => self::TEST_HTTP_HOST,
        );

        $force_path = array(self::TEST_PARAM_DIR_NAME2 => $this->root.'/'.self::TEST_SEARCH_DIR.'/'.self::TEST_PARAM_DIR_NAME2.'/Seed.ini');

        $configurator = new Configurator();
        $configurator->initialize($this->root, self::TEST_SEARCH_DIR, $force_path);

        $ini = parse_ini_file($force_path[self::TEST_PARAM_DIR_NAME2], true, INI_SCANNER_TYPED);

        $this->assertEquals($configurator->getSeed(), $ini);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_initialize_InifileNull()
    {
        $_SERVER = array(
            'SERVER_NAME' => self::TEST_SERVER_NAME2,
            'HTTP_HOST' => self::TEST_HTTP_HOST,
        );

        $configurator = new Configurator();
        $this->assertEquals($configurator->initialize($this->root, ''), '');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_initialize_ServerNotMatch()
    {
        $_SERVER = array(
            'SERVER_NAME' => self::TEST_SERVER_NAME2,
            'HTTP_HOST' => self::TEST_HTTP_HOST,
        );

        $force_path = array(self::TEST_PARAM_DIR_NAME1 => $this->root.'/'.self::TEST_SEARCH_DIR.'/'.self::TEST_PARAM_DIR_NAME1.'/Seed.ini');

        $configurator = new Configurator();

        $this->assertEquals($configurator->initialize($this->root, self::TEST_SEARCH_DIR, $force_path), '');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_initialize_setter()
    {
        $_SERVER = array(
            'SERVER_NAME' => self::TEST_SERVER_NAME1,
            'HTTP_HOST' => self::TEST_HTTP_HOST,
        );

        $configurator = new Configurator();
        $out = $configurator->initialize($this->root, self::TEST_SEARCH_DIR);

        $path = $this->root.'/'.self::TEST_SEARCH_DIR.'/'.self::TEST_PARAM_DIR_NAME1.'/Seed.ini';
        $ini = parse_ini_file($path, true, INI_SCANNER_TYPED);

        $this->assertEquals($configurator->read('config'), $ini['APPLICATION']);
        $this->assertEquals($configurator->read('title_prefix'), $ini['HTML_OPTION']['title_prefix']);
        $this->assertEquals($configurator->read('title_suffix'), $ini['HTML_OPTION']['title_suffix']);

        $this->assertEquals($out, self::TEST_PARAM_DIR_NAME1);
    }

    // Test for read
    /**
     * @runInSeparateProcess
     */
    public function test_read_NotMatch()
    {
        $_SERVER = array(
            'SERVER_NAME' => self::TEST_SERVER_NAME1,
            'HTTP_HOST' => self::TEST_HTTP_HOST,
        );

        $configurator = new Configurator();
        $configurator->initialize($this->root, self::TEST_SEARCH_DIR);

        $this->assertEquals($configurator->read('hoge'), '');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_read_NotString()
    {
        $_SERVER = array(
            'SERVER_NAME' => self::TEST_SERVER_NAME1,
            'HTTP_HOST' => self::TEST_HTTP_HOST,
        );

        $configurator = new Configurator();
        $configurator->initialize($this->root, self::TEST_SEARCH_DIR);

        $this->assertEquals($configurator->read(9), '');
    }
}
