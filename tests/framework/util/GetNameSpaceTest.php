<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\util\GetNameSpace;

class GetNameSpaceTest extends TestCase
{
    //Test for getApp
    public function test_getApp_NormalCase()
    {
        $out = (new GetNameSpace())->getApp('hoge');

        $this->assertEquals($out, '\\artichoke\\hoge');
    }

    public function test_getApp_InputEmpty()
    {
        $out = (new GetNameSpace())->getApp('');

        $this->assertEquals($out, '\\artichoke\\');
    }

    //Test for getAppController
    public function test_getAppController_NormalCase()
    {
        $out = (new GetNameSpace())->getAppController('hoge');

        $this->assertEquals($out, '\\artichoke\\hoge\\controllers\\');
    }

    public function test_getAppController_InputEmpty()
    {
        $out = (new GetNameSpace())->getAppController('');

        $this->assertEquals($out, '\\artichoke\\\\controllers\\');
    }

    //Test for getFrameworkController
    public function test_getFrameworkController_NormalCase()
    {
        $out = (new GetNameSpace())->getFrameworkController();

        $this->assertEquals($out, '\\artichoke\\framework\\controllers\\');
    }

    //Test for getFrameworkModelsStaticdbUserModel
    public function test_getFrameworkModelsStaticdbUserModel_NormalCase()
    {
        $out = (new GetNameSpace())->getFrameworkModelsStaticdbUserModel();

        $this->assertEquals($out, '\\artichoke\\framework\\models\\client\\User');
    }

    //Test for getModelsAnalyticsModel
    public function test_getModelsAnalyticsModel_NormalCase()
    {
        $out = (new GetNameSpace())->getModelsAnalyticsModel('hoge', 'piyo\\');

        $this->assertEquals($out, '\\artichoke\\hoge\\models\\analytics\\piyo\\Model');
    }

    //Test for getFrameworkModelsAnalyticsModel
    public function test_getFrameworkModelsAnalyticsModel_NormalCase()
    {
        $out = (new GetNameSpace())->getFrameworkModelsAnalyticsModel('piyo\\');

        $this->assertEquals($out, '\\artichoke\\framework\\models\\analytics\\piyo\\Model');
    }
}
