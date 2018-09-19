<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\abstracts\AnalyticsBase;

class AnalyticsBaseTest extends TestCase
{
    private $stub;
    /**
     * @before
     */
    public function gen_stub()
    {
        $this->stub = $this->getMockForAbstractClass(AnalyticsBase::class);
    }

    /**
     * @doesNotPerformAssertions
     */
    private function boundSetRequiredParams()
    {
        $setter = function ($str) {
            return $this->setRequiredParams($str);
        };

        $bound = $setter->bindTo($this->stub, $this->stub);

        return $bound;
    }

    // Test for requiredParams
    public function test_requiredParams_UseCase()
    {
        $this->boundSetRequiredParams()('test');

        $res = $this->stub->requiredParams();

        $this->assertEquals($res, 'test');
    }

    public function test_requiredParams_Null()
    {
        $this->boundSetRequiredParams()(null);

        $res = $this->stub->requiredParams();

        $this->assertNull($res);
    }

    // Test for requiredParamsWithString
    public function test_requiredParamsWithString_Pattern0()
    {
        $this->boundSetRequiredParams()(0);

        $res = $this->stub->requiredParamsWithString();

        $this->assertEquals($res, 'No any params');
    }

    public function test_requiredParamsWithString_Pattern0String()
    {
        $this->boundSetRequiredParams()('0');

        $res = $this->stub->requiredParamsWithString();

        $this->assertEquals($res, '');
    }

    public function test_requiredParamsWithString_PatternREQUIRE_ARRAY()
    {
        $this->boundSetRequiredParams()(AnalyticsBase::REQUIRE_ARRAY);

        $res = $this->stub->requiredParamsWithString();

        $this->assertEquals($res, 'Array');
    }

    public function test_requiredParamsWithString_PatternREQUIRE_STRING()
    {
        $this->boundSetRequiredParams()(AnalyticsBase::REQUIRE_STRING);

        $res = $this->stub->requiredParamsWithString();

        $this->assertEquals($res, 'String');
    }

    public function test_requiredParamsWithString_Pattern3()
    {
        $this->boundSetRequiredParams()(3);

        $res = $this->stub->requiredParamsWithString();

        $this->assertEquals($res, 'Array,String');
    }

    public function test_requiredParamsWithString_PatternREQUIRE_FILEPATH()
    {
        $this->boundSetRequiredParams()(AnalyticsBase::REQUIRE_FILEPATH);

        $res = $this->stub->requiredParamsWithString();

        $this->assertEquals($res, 'File path');
    }

    public function test_requiredParamsWithString_Pattern5()
    {
        $this->boundSetRequiredParams()(5);

        $res = $this->stub->requiredParamsWithString();

        $this->assertEquals($res, 'Array,File path');
    }

    public function test_requiredParamsWithString_Pattern6()
    {
        $this->boundSetRequiredParams()(6);

        $res = $this->stub->requiredParamsWithString();

        $this->assertEquals($res, 'String,File path');
    }

    public function test_requiredParamsWithString_Pattern7()
    {
        $this->boundSetRequiredParams()(7);

        $res = $this->stub->requiredParamsWithString();

        $this->assertEquals($res, 'Array,String,File path');
    }

    // Test for setParams
    public function test_setParams_RequiredParamsREQUIRE_ARRAYParamsArray()
    {
        $this->boundSetRequiredParams()(AnalyticsBase::REQUIRE_ARRAY);

        $res = $this->stub->setParams(array());

        $this->assertTrue($res);
        $this->assertEquals($this->stub->getParams(), array());
    }

    public function test_setParams_RequiredParamsREQUIRE_ARRAYParamsNotArray()
    {
        $this->boundSetRequiredParams()(AnalyticsBase::REQUIRE_ARRAY);

        $res = $this->stub->setParams('');

        $this->assertFalse($res);
        $this->assertNull($this->stub->getParams());
    }

    public function test_setParams_RequiredParamREQUIRE_STRINGParamsString()
    {
        $this->boundSetRequiredParams()(AnalyticsBase::REQUIRE_STRING);

        $res = $this->stub->setParams('hoge');

        $this->assertTrue($res);
        $this->assertEquals($this->stub->getParams(), 'hoge');
    }

    public function test_setParams_RequiredParamREQUIRE_STRINGParamsNotString()
    {
        $this->boundSetRequiredParams()(AnalyticsBase::REQUIRE_STRING);

        $res = $this->stub->setParams(1);

        $this->assertFalse($res);
        $this->assertNull($this->stub->getParams());
    }

    public function test_setParams_RequiredParamREQUIRE_FILEPATHParamsFile()
    {
        $this->boundSetRequiredParams()(AnalyticsBase::REQUIRE_FILEPATH);

        $res = $this->stub->setParams(__FILE__);

        $this->assertTrue($res);
        $this->assertEquals($this->stub->getParams(), __FILE__);
    }

    public function test_setParams_RequiredParamREQUIRE_FILEPATHParamsUnreadableFile()
    {
        $this->boundSetRequiredParams()(AnalyticsBase::REQUIRE_FILEPATH);

        $res = $this->stub->setParams(__DIR__.'unread.php');

        $this->assertFalse($res);
        $this->assertNull($this->stub->getParams());
    }

    public function test_setParams_RequiredParam0()
    {
        $this->boundSetRequiredParams()(AnalyticsBase::REQUIRE_NONE);

        $res = $this->stub->setParams(__DIR__.'unread.php');

        $this->assertTrue($res);
        $this->assertNull($this->stub->getParams());
    }
}
