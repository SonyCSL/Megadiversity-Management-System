<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\abstracts\AsyncBase;

class AsyncBaseTest extends TestCase
{
    private $stub;

    /**
     * @doesNotPerformAssertions
     */
    public function initialize()
    {
        $_SERVER['SERVER_PROTOCOL'] = 'protocol';
        $stub = $this->getMockForAbstractClass(AsyncBase::class);

        return $stub;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_set($stub)
    {
        $setter = function ($key, $value) {
            return $this->set($key, $value);
        };
        $bound = $setter->bindTo($stub, $stub);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_aSet($stub)
    {
        $setter = function ($key, $value) {
            return $this->aSet($key, $value);
        };
        $bound = $setter->bindTo($stub, $stub);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_copy($stub)
    {
        $setter = function ($value) {
            return $this->copy($value);
        };
        $bound = $setter->bindTo($stub, $stub);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_setFormat($stub)
    {
        $setter = function ($type = null) {
            return $this->setFormat($type);
        };
        $bound = $setter->bindTo($stub, $stub);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_respond($stub)
    {
        $setter = function ($output = '') {
            return $this->respond($output);
        };
        $bound = $setter->bindTo($stub, $stub);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_respondln($stub)
    {
        $setter = function ($output = '') {
            return $this->respondln($output);
        };
        $bound = $setter->bindTo($stub, $stub);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_clear($stub)
    {
        $setter = function ($key = null) {
            return $this->clear($key);
        };
        $bound = $setter->bindTo($stub, $stub);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_exception($stub)
    {
        $setter = function ($args = null) {
            return $this->exception($args);
        };
        $bound = $setter->bindTo($stub, $stub);

        return $bound;
    }

    // Test for set
    /**
     * @runInSeparateProcess
     */
    public function test_set_UseCase()
    {
        $stub = $this->initialize();
        $this->bound_set($stub)('key', 9);

        $res = $stub->getAllParams();

        $this->assertEquals($res['format'], 'json');
        $this->assertEquals($res['variables']['key'], 9);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_set_Null()
    {
        $stub = $this->initialize();
        $this->bound_set($stub)(null, null);

        $res = $stub->getAllParams();

        $this->assertEquals($res['format'], 'json');
        $this->assertEquals($res['variables'], array('' => null));
    }

    // Test for aSet
    /**
     * @runInSeparateProcess
     */
    public function test_aSet_UseCase()
    {
        $stub = $this->initialize();
        $this->bound_aSet($stub)('key', array('hoge' => 'piyo'));

        $res = $stub->getAllParams();

        $this->assertEquals($res['format'], 'json');
        $this->assertEquals($res['variables'], array('key' => array(array('hoge' => 'piyo'))));
    }

    /**
     * @runInSeparateProcess
     */
    public function test_aSet_Null()
    {
        $stub = $this->initialize();
        $this->bound_aSet($stub)(null, null);

        $res = $stub->getAllParams();

        $this->assertEquals($res['format'], 'json');
        $this->assertEquals($res['variables'], array('' => array(null)));
    }

    // test for copy
    /**
     * @runInSeparateProcess
     */
    public function test_copy()
    {
        $stub = $this->initialize();
        $setter = function ($value) {
            return $this->copy($value);
        };
        $bound = $setter->bindTo($stub, $stub);
        $bound('test');

        $res = $stub->getAllParams();

        $this->assertEquals($res['variables'], 'test');
    }

    // test for setFormat
    /**
     * @runInSeparateProcess
     */
    public function test_setFormat_Set()
    {
        $stub = $this->initialize();
        $this->bound_setFormat($stub)('test');

        $res = $stub->getAllParams();

        $this->assertEquals($res['format'], 'test');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_respond_UseCase()
    {
        $stub = $this->initialize();
        $bound = $this->bound_respond($stub);

        // First
        $bound('test1');

        $res = $stub->getAllParams();

        $this->assertEquals($res['format'], 'text');
        $this->assertEquals($res['rawText'], 'test1');

        // Second
        $bound(2);

        $res = $stub->getAllParams();

        $this->assertEquals($res['rawText'], 'test12');

        // Third
        $bound();

        $res = $stub->getAllParams();

        $this->assertEquals($res['rawText'], 'test12');

        // Last
        $bound('test4');

        $res = $stub->getAllParams();

        $this->assertEquals($res['rawText'], 'test12test4');
    }

    // test for respondln
    /**
     * @runInSeparateProcess
     */
    public function test_respondln_UseCase()
    {
        $stub = $this->initialize();
        $bound = $this->bound_respondln($stub);

        // First
        $bound('test1');

        $res = $stub->getAllParams();

        $this->assertEquals($res['format'], 'text');
        $this->assertEquals($res['rawText'], 'test1'."\n");

        // Second
        $bound(2);

        $res = $stub->getAllParams();

        $this->assertEquals($res['rawText'], 'test1'."\n".'2'."\n");

        // Third
        $bound();

        $res = $stub->getAllParams();

        $this->assertEquals($res['rawText'], 'test1'."\n".'2'."\n"."\n");

        // Last
        $bound('test4');

        $res = $stub->getAllParams();

        $this->assertEquals($res['rawText'], 'test1'."\n".'2'."\n"."\n".'test4'."\n");
    }

    // Test for clear
    /**
     * @runInSeparateProcess
     */
    public function test_clear_KeyExists()
    {
        $stub = $this->initialize();
        $this->bound_set($stub)('key', 'set');
        $this->bound_respond($stub)('rawText');

        $res = $stub->getAllParams();

        $this->assertEquals($res['variables']['key'], 'set');
        $this->assertEquals($res['rawText'], 'rawText');

        // clear 'key'
        $this->bound_clear($stub)('key');

        $res = $stub->getAllParams();

        $this->assertEquals($res['variables'], array());
        $this->assertEquals($res['rawText'], 'rawText');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_clear_KeyNull()
    {
        $stub = $this->initialize();
        $this->bound_set($stub)('key', 'set');
        $this->bound_respond($stub)('rawText');

        $res = $stub->getAllParams();

        $this->assertEquals($res['variables']['key'], 'set');
        $this->assertEquals($res['rawText'], 'rawText');

        // clear 'key'
        $this->bound_clear($stub)();

        $res = $stub->getAllParams();

        $this->assertEquals($res['variables'], array());
        $this->assertEquals($res['rawText'], '');
        $this->assertEquals($res['format'], '?');
    }

    // Test for exception
    /**
     * @runInSeparateProcess
     */
    public function test_exception_args5()
    {
        $stub = $this->initialize();
        $this->bound_exception($stub)(array('test0', 'test1', 'test2', 'test3', 'test4'));

        $res = $stub->getAllParams();

        $this->assertEquals($res['variables']['detail'], 'test1');
        $this->assertEquals($res['variables']['exCode'], 'test0');
        $this->assertEquals($res['variables']['params'], 'test4');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_exception_args6()
    {
        $stub = $this->initialize();
        $this->bound_exception($stub)(array('test0', 'test1', 'test2', 'test3', 'test4', 0));

        $res = $stub->getAllParams();

        $this->assertEquals($res['variables']['detail'], 'test1');
        $this->assertEquals($res['variables']['exCode'], 'test0');
        $this->assertEquals($res['variables']['params'], 'test4');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_exception_argsNotArray()
    {
        $stub = $this->initialize();
        $this->bound_exception($stub)(10);

        $res = $stub->getAllParams();

        $this->assertEquals($res['variables']['detail'], 'Unknow exception is thrown');
        $this->assertEquals($res['variables']['exCode'], 0);
        $this->assertEquals($res['variables']['params'], 'unknown');
    }

    // test for destructor
    /**
     * @runInSeparateProcess
     */
    public function test_destructor_json()
    {
        $stub = $this->initialize();

        // set Ajax pattern
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

        $this->bound_copy($stub)(array('hoge' => 'piyo', 'key' => 'value'));

        $this->expectOutputString('{"hoge":"piyo","key":"value"}');
    }

    // test for destructor
    /**
     * @runInSeparateProcess
     */
    public function test_destructor_text()
    {
        $stub = $this->initialize();

        // set Ajax pattern
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

        $this->bound_respond($stub)('respond');

        $this->expectOutputString('respond');
    }
}
