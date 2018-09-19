<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;

use artichoke\framework\core\Configurator;
use artichoke\framework\abstracts\ControllerBase;

defined('UNIT_TESTING');

class ControllerBaseTest extends TestCase
{
    private $root;
    private $stub;

    /**
     * @doesNotPerformAssertions
     */
    public function gen_root_dir()
    {
        $divided_dir = explode('/', __DIR__);

        $this->root = '';
        foreach ($divided_dir as $val) {
            if ($val !== '') {
                if ($val === 'tests') {
                    break;
                }
                $this->root = $this->root.'/'.$val;
            }
        }
    }

    /**
     * @doesNotPerformAssertions
     */
    public function set_vals()
    {
        $_SERVER['SERVER_NAME'] = 'hoge';
        $this->initialize();
        $stub = $this->getMockForAbstractClass(ControllerBase::class);

        return $stub;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function initialize()
    {
        $this->gen_root_dir();
        $force_path = array('framework' => $this->root.'/tests/testparam2/Seed.ini');
        $config = (new Configurator())->initialize($this->root, 'artichoke', $force_path);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_set($stub)
    {
        $setter = function (string $key, $value) {
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
        $setter = function (string $key, $value) {
            return $this->aSet($key, $value);
        };
        $bound = $setter->bindTo($stub, $stub);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_clear($stub)
    {
        $setter = function (string $key = null) {
            return $this->clear($key);
        };
        $bound = $setter->bindTo($stub, $stub);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_aClear($stub)
    {
        $setter = function (string $key = null) {
            return $this->aClear($key);
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

    //Test for set
    /**
     * @runInSeparateProcess
     */
    public function test_set()
    {
        $stub = $this->set_vals();
        $this->bound_set($stub)('key', 99);

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageVariables']['key'], 99);
    }

    //Test for aSet
    /**
     * @runInSeparateProcess
     */
    public function test_aSet()
    {
        $stub = $this->set_vals();

        $setter = function (string $key, $value) {
            return $this->aSet($key, $value);
        };
        $bound = $setter->bindTo($stub, $stub);
        $bound('aSet', 999);

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageArrays'], array('aSet' => array(999)));
    }

    //Test for aCopy
    /**
     * @runInSeparateProcess
     */
    public function test_aCopy()
    {
        $stub = $this->set_vals();

        $setter = function (string $key, $value) {
            return $this->aCopy($key, $value);
        };
        $bound = $setter->bindTo($stub, $stub);
        $bound('aCopy', 9999);

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageArrays'], array('aCopy' => 9999));
    }

    //Test for Clear
    /**
     * @runInSeparateProcess
     */
    public function test_Clear_KeyNull()
    {
        $stub = $this->set_vals();
        $this->bound_set($stub)('clear1', 'c1');

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageVariables']['clear1'], 'c1');

        $this->bound_clear($stub)();

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageVariables'], []);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_Clear_KeySet()
    {
        $stub = $this->set_vals();
        $this->bound_set($stub)('clear1', 'c1');

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageVariables']['clear1'], 'c1');

        $this->bound_clear($stub)('clear1');

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageVariables'], []);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_Clear_KeyNotMatch()
    {
        $stub = $this->set_vals();
        $this->bound_set($stub)('clear1', 'c1');

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageVariables']['clear1'], 'c1');

        $this->bound_clear($stub)('clear2');

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageVariables']['clear1'], 'c1');
    }

    //Test for aClear
    /**
     * @runInSeparateProcess
     */
    public function test_aClear_KeyNull()
    {
        $stub = $this->set_vals();
        $this->bound_aSet($stub)('clear2', 'c2');

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageArrays'], array('clear2' => array('c2')));

        $this->bound_aClear($stub)();

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageArrays'], []);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_aClear_KeyMatch()
    {
        $stub = $this->set_vals();
        $this->bound_aSet($stub)('clear2', 'c2');

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageArrays'], array('clear2' => array('c2')));

        $this->bound_aClear($stub)('clear2');

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageArrays'], []);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_aClear_KeyNotMatch()
    {
        $stub = $this->set_vals();
        $this->bound_aSet($stub)('clear2', 'c2');

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageArrays'], array('clear2' => array('c2')));

        $this->bound_aClear($stub)('clear3');

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageArrays'], array('clear2' => array('c2')));
    }

    //Test for showDump
    /**
     * @runInSeparateProcess
     */
    public function test_showDump()
    {
        $stub = $this->set_vals();

        $setter = function () {
            return $this->showDump();
        };
        $bound = $setter->bindTo($stub, $stub);
        $bound();

        $res = $stub->getAllParams();

        $this->assertTrue($res['dump']);
    }

    //Test for reload
    /**
     * @runInSeparateProcess
     */
    public function test_exception_args5()
    {
        $stub = $this->set_vals();

        $_SERVER['SERVER_PROTOCOL'] = 'protocol';

        $inputs = array('args0', 'args1', 'args2', 'args3', 'args4');

        $this->bound_exception($stub)($inputs);

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageVariables']['detail'], 'args1');
        $this->assertEquals($res['pageVariables']['exCode'], 'args0');
        $this->assertEquals($res['pageVariables']['params'], 'args4');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_exception_args6()
    {
        $stub = $this->set_vals();

        $_SERVER['SERVER_PROTOCOL'] = 'protocol';

        $inputs = array('args0', 'args1', 'args2', 'args3', 'args4', 5);

        $this->bound_exception($stub)($inputs);

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageVariables']['detail'], 'args1');
        $this->assertEquals($res['pageVariables']['exCode'], 'args0');
        $this->assertEquals($res['pageVariables']['params'], 'args4');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_exception_argsNotArray()
    {
        $stub = $this->set_vals();

        $_SERVER['SERVER_PROTOCOL'] = 'protocol';

        $this->bound_exception($stub)(0);

        $res = $stub->getAllParams();

        $this->assertEquals($res['pageVariables']['detail'], 'Unknow exception is thrown');
        $this->assertEquals($res['pageVariables']['exCode'], 0);
        $this->assertEquals($res['pageVariables']['params'], 'unknown');
    }
}
