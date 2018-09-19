<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\util\GetPaths;

class GetPathsTest extends TestCase
{
    //Test for getRequestedPaths
    public function test_getRequestedPaths_NoQestion()
    {
        $test_pattern = 'hoge/119/piyo';

        $out = (new GetPaths())->getRequestedPaths($test_pattern);

        $this->assertEquals($out, $test_pattern);
    }

    public function test_getRequestedPaths_InputEmpty()
    {
        $out = (new GetPaths())->getRequestedPaths('');

        $this->assertEquals($out, '');
    }

    public function test_getRequestedPaths_InputQestion()
    {
        $test_pattern = 'hoge?119/piyo';

        $out = (new GetPaths())->getRequestedPaths($test_pattern);

        $this->assertEquals($out, 'hoge');
    }

    public function test_getRequestedPaths_InputSequencialQestion()
    {
        $test_pattern = 'hoge??119?piyo';

        $out = (new GetPaths())->getRequestedPaths($test_pattern);

        $this->assertEquals($out, 'hoge');
    }

    public function test_getRequestedPaths_InputAllQestion()
    {
        $test_pattern = '???';

        $out = (new GetPaths())->getRequestedPaths($test_pattern);

        $this->assertEquals($out, '');
    }

    // Test for splitUri
    public function test_splitUri_NormalCase()
    {
        $out = (new GetPaths())->splitUri('1/2/3/');

        $this->assertEquals($out[0], '1');
        $this->assertEquals($out[1], '2');
        $this->assertEquals($out[2], '3');
        $this->assertEquals($out[3], '');
    }

    public function test_splitUri_InputEmpty()
    {
        $out = (new GetPaths())->splitUri('');

        $this->assertEquals($out[0], 'index');
        $this->assertTrue(empty($out[1]));
    }

    public function test_splitUri_InputSequencialSlash()
    {
        $out = (new GetPaths())->splitUri('//a//');

        $this->assertEquals($out[0], '');
        $this->assertEquals($out[1], '');
        $this->assertEquals($out[2], 'a');
        $this->assertEquals($out[3], '');
        $this->assertEquals($out[4], '');
    }

    //Test for getAppPath
    public function test_getAppPath_NormalCase()
    {
        $out = (new GetPaths())->getAppPath('hoge', 'piyo');

        $this->assertEquals($out, 'hoge/artichoke/piyo');
    }

    public function test_getAppPath_InputEmptyRoot()
    {
        $out = (new GetPaths())->getAppPath('', 'piyo');

        $this->assertEquals($out, '/artichoke/piyo');
    }

    public function test_getAppPath_InputEmptyApp()
    {
        $out = (new GetPaths())->getAppPath('hoge', '');

        $this->assertEquals($out, 'hoge/artichoke/');
    }

    public function test_getAppPath_InputEmptyAll()
    {
        $out = (new GetPaths())->getAppPath('', '');

        $this->assertEquals($out, '/artichoke/');
    }

    //Test for getAppViewPath
    public function test_getAppViewPath_NormalCase()
    {
        $out = (new GetPaths())->getAppViewPath('hoge', 'piyo');

        $this->assertEquals($out, 'hoge/artichoke/piyo/views');
    }

    public function test_getAppViewPath_InputRootEmpty()
    {
        $out = (new GetPaths())->getAppViewPath('', 'piyo');

        $this->assertEquals($out, '/artichoke/piyo/views');
    }

    public function test_getAppViewPath_InputAppEmpty()
    {
        $out = (new GetPaths())->getAppViewPath('hoge', '');

        $this->assertEquals($out, 'hoge/artichoke//views');
    }

    public function test_getAppViewPath_InputAllEmpty()
    {
        $out = (new GetPaths())->getAppViewPath('', '');

        $this->assertEquals($out, '/artichoke//views');
    }

    //Test for getFrameworkViewPath
    public function test_getFrameworkViewPath_NormalCase()
    {
        $out = (new GetPaths())->getFrameworkViewPath('hoge');

        $this->assertEquals($out, 'hoge/artichoke/framework/views');
    }

    public function test_getFrameworkViewPath_Empty()
    {
        $out = (new GetPaths())->getFrameworkViewPath('');

        $this->assertEquals($out, '/artichoke/framework/views');
    }

    //Test for getAppPublicPath
    public function test_getAppPublicPath_NormalCase()
    {
        $out = (new GetPaths())->getAppPublicPath('hoge', 'piyo');

        $this->assertEquals($out, 'hoge/artichoke/piyo/views/public/');
    }

    //Test for getAppProtectedPath
    public function test_getAppProtectedPath_NormalCase()
    {
        $out = (new GetPaths())->getAppProtectedPath('hoge', 'piyo');

        $this->assertEquals($out, 'hoge/artichoke/piyo/views/protected/');
    }

    //Test for getFrameworkResourcePath
    public function test_getFrameworkResourcePath_NormalCase()
    {
        $out = (new GetPaths())->getFrameworkResourcePath('hoge');

        $this->assertEquals($out, 'hoge/artichoke/framework/resources/');
    }

    //Test for getAppControllerPath
    public function test_getAppControllerPath_NormalCase()
    {
        $out = (new GetPaths())->getAppControllerPath('hoge', 'piyo');

        $this->assertEquals($out, 'hoge/artichoke/piyo/controllers/');
    }

    //Test for getAppControllerPath
    public function test_getFrameworkControllerPath_NormalCase()
    {
        $out = (new GetPaths())->getFrameworkControllerPath('hoge');

        $this->assertEquals($out, 'hoge/artichoke/framework/controllers/');
    }

    //Test for getTemplatePath
    public function test_getTemplatePath_NormalCase()
    {
        $out = (new GetPaths())->getTemplatePath('hoge');

        $this->assertEquals($out, 'hoge/template/');
    }

    public function test_getTemplatePath_Null()
    {
        $out = (new GetPaths())->getTemplatePath('');

        $this->assertEquals($out, '/template/');
    }

    //Test for getFilePathToExceptionHtml
    public function test_getFilePathToExceptionHtml_NormalCase()
    {
        $out = (new GetPaths())->getFilePathToExceptionHtml('root');

        $this->assertEquals($out, 'root/artichoke/framework/views/template/exception.html');
    }

    //Test for getFilePathToExceptionHtml
    public function test_getFilePathToIndexHtml_NormalCase()
    {
        $out = (new GetPaths())->getFilePathToIndexHtml('upper/test');

        $this->assertEquals($out, 'upper/test/index.html');
    }

    public function test_getFilePathToIndexHtml_Null()
    {
        $out = (new GetPaths())->getFilePathToIndexHtml('');

        $this->assertEquals($out, '/index.html');
    }

    //Test for genHtmlFileName
    public function test_genHtmlFileName_NormalCase()
    {
        $out = (new GetPaths())->genHtmlFileName('upper/test/index');

        $this->assertEquals($out, 'upper/test/index.html');
    }

    public function test_genHtmlFileName_Null()
    {
        $out = (new GetPaths())->genHtmlFileName('');

        $this->assertEquals($out, '.html');
    }
}
