<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\core\Server;
use artichoke\framework\util\LoadResource;

class LoadResourceTest extends TestCase
{
    private $search_root;

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

        $this->root = $this->root.'/artichoke/framework/util/';
    }

    // Test for loadFileFromPath
    /**
     * @runInSeparateProcess
     */
    public function test_loadFileFromPath_FileExists()
    {
        $server = new Server($_SERVER);
        $out = (new LoadResource())->loadFileFromPath($this->root.'LoadResource.php', $server);

        $this->assertTrue($out);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_loadFileFromPath_FileNotExists()
    {
        $server = new Server($_SERVER);
        $out = (new LoadResource())->loadFileFromPath($this->root.'LoadResources.php', $server);

        $this->assertFalse($out);
    }

    // Test for loadFileFromArray
    /**
     * @runInSeparateProcess
     */
    public function test_loadFileFromArray_FileExists()
    {
        $path_array = array(
            $this->root.'LoadResources.php',
            $this->root.'LoadResouces.php',
            $this->root.'LoadResource.php',
        );

        $server = new Server($_SERVER);
        $out = (new LoadResource())->loadFileFromArray($path_array, $server);

        $this->assertTrue($out);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_loadFileFromArray_FileNotExists()
    {
        $path_array = array(
            $this->root.'LoadResources.php',
            $this->root.'LoadResouces.php',
            $this->root.'LoadResourca.php',
        );

        $server = new Server($_SERVER);
        $out = (new LoadResource())->loadFileFromArray($path_array, $server);

        $this->assertFalse($out);
    }

    // Test for loadFileFromArray
    /**
     * @runInSeparateProcess
     */
    public function test_loadFileFromArrayAndExit_FileExists()
    {
        $path_array = array(
            $this->root.'LoadResource.php',
            $this->root.'LoadResouces.php',
            $this->root.'LoadResourca.php',
        );

        $server = new Server($_SERVER);
        $out = (new LoadResource())->loadFileFromArrayAndExit($path_array, $server);

        $this->assertTrue($out);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_loadFileFromArrayAndExit_FileNotExists()
    {
        $path_array = array(
            $this->root.'LoadResources.php',
            $this->root.'LoadResouces.php',
            $this->root.'LoadResourca.php',
        );

        $_SERVER['SERVER_PROTOCOL'] = '';

        $server = new Server($_SERVER);
        $out = (new LoadResource())->loadFileFromArrayAndExit($path_array, $server);

        $this->assertFalse($out);
    }
}
