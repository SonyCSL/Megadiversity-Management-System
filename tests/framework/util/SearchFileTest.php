<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\util\SearchFile;

class SearchFileTest extends TestCase
{
    const TEST_FILE_NAME = 'Seed.ini';

    private $root;
    /**
     * @before
     */
    public function gen_root_dir()
    {
        $divided_dir = explode('/', __DIR__);

        $this->root = '';
        foreach ($divided_dir as $val) {
            if ($val !== '') {
                if ($val === 'framework') {
                    break;
                }
                $this->root = $this->root.'/'.$val;
            }
        }
    }

    // Test for searchFilePath
    public function test_searchFilePath_PathExistFileExist()
    {
        $path = new searchFile($this->root.'/');

        $out = $path->searchFilePath(self::TEST_FILE_NAME);

        $this->assertEquals($out['testparam1'], $this->root.'/testparam1/'.self::TEST_FILE_NAME);
        $this->assertEquals($out['testparam2'], $this->root.'/testparam2/'.self::TEST_FILE_NAME);
    }

    public function test_searchFilePath_PathNotExistFileExist()
    {
        $path = new searchFile($this->root);

        $out = $path->searchFilePath(self::TEST_FILE_NAME);

        $this->assertEquals($out, array());
    }

    public function test_searchFilePath_PathExistFileNotExist()
    {
        $path = new searchFile($this->root);

        $out = $path->searchFilePath('hoge.ini');

        $this->assertEquals($out, array());
    }
}
