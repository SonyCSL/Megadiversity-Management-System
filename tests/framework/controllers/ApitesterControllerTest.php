<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\controllers\ApitesterController;
use artichoke\framework\core\Configurator;
use artichoke\framework\core\Requests;

require_once dirname(dirname(__DIR__)).'/common/GenRootDir.php';

class ApitesterControllerTest extends TestCase
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
    public function bound_setResult($testClass)
    {
        $setter = function ($returned_data_body, $returned_content_type) {
            return $this->setResult($returned_data_body, $returned_content_type);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_setPostAction($testClass)
    {
        $setter = function (Requests $request) {
            return $this->setPostAction($request);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_setAnalyticsAction($testClass)
    {
        $setter = function (Requests $request) {
            return $this->setAnalyticsAction($request);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    //test for setResult
    /**
     * @runInSeparateProcess
     */
    public function test_setResultNotImage()
    {
        $this->initialize();
        $apitester = new ApitesterController();

        $ref = new ReflectionClass(get_class($apitester));

        $returned_content_type = 'test';
        $returned_data_body = '{"a":1,"b":2,"c":3,"d":4,"e":5}';

        $this->bound_setResult($apitester)($returned_data_body, $returned_content_type);

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($apitester);

        $expect_out = "<pre>\n{\n    \"a\": 1,\n    \"b\": 2,\n    \"c\": 3,\n    \"d\": 4,\n    \"e\": 5\n}\n</pre>\n";
        $this->assertEquals($out, $expect_out);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_setResultImage()
    {
        $this->initialize();
        $apitester = new ApitesterController();

        $ref = new ReflectionClass(get_class($apitester));

        $returned_content_type = 'image/';
        $returned_data_body = 'test_data';

        $this->bound_setResult($apitester)($returned_data_body, $returned_content_type);

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($apitester);

        $expect_out = '<img width="100%" src="data:image/;base64,'.base64_encode($returned_data_body).'">';
        $this->assertEquals($out, $expect_out);
    }

    //test for setAnalyticsAction
    /**
     * @runInSeparateProcess
     */
    public function test_setAnalyticsActionTargetFile()
    {
        $this->initialize();
        $apitester = new ApitesterController();

        $request_input_array = array('targetselect' => 'file');
        $file_input_array = array('file' => ['tmp_name' => __FILE__]);
        $request = new Requests($request_input_array, $file_input_array);

        $ref = new ReflectionClass(get_class($apitester));

        $out = $this->bound_setAnalyticsAction($apitester)($request);

        $filepath = $request->filepath('file');
        $this->assertEquals($out['file'], new \CURLFile($filepath, mime_content_type($filepath), 'APITESTFILE'));
    }

    /**
     * @runInSeparateProcess
     */
    public function test_setAnalyticsActionTargetEntryid()
    {
        $this->initialize();
        $apitester = new ApitesterController();

        $request_input_array = array('targetselect' => 'entryid', 'entryid' => 'entry');
        $file_input_array = array('file' => ['tmp_name' => __FILE__]);
        $request = new Requests($request_input_array, $file_input_array);

        $ref = new ReflectionClass(get_class($apitester));

        $out = $this->bound_setAnalyticsAction($apitester)($request);

        $this->assertEquals($out['entryid'], 'entry');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_setAnalyticsActionTargetAlbumid()
    {
        $this->initialize();
        $apitester = new ApitesterController();

        $request_input_array = array('targetselect' => 'albumid', 'albumid' => 'album');
        $file_input_array = array('file' => ['tmp_name' => __FILE__]);
        $request = new Requests($request_input_array, $file_input_array);

        $ref = new ReflectionClass(get_class($apitester));

        $out = $this->bound_setAnalyticsAction($apitester)($request);

        $this->assertEquals($out['albumid'], 'album');
    }

    // test for setPostAction
    /**
     * @runInSeparateProcess
     */
    public function test_setPostAction_FilepathNull()
    {
        $this->initialize();
        $apitester = new ApitesterController();

        $request_input_array = array('targetselect' => 'albumid', 'albumid' => 'album');
        $file_input_array = array('file1' => ['tmp_name' => 'NotMatch']);
        $request = new Requests($request_input_array, $file_input_array);

        $ref = new ReflectionClass(get_class($apitester));

        $out = $this->bound_setPostAction($apitester)($request);

        $this->assertEquals($out, []);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_setPostAction_FileExists()
    {
        $this->initialize();
        $apitester = new ApitesterController();

        $request_input_array = array('targetselect' => 'file');
        $file_input_array = array('file' => ['tmp_name' => __FILE__]);
        $request = new Requests($request_input_array, $file_input_array);

        $ref = new ReflectionClass(get_class($apitester));

        $out = $this->bound_setPostAction($apitester)($request);

        $filepath = $request->filepath('file');
        $this->assertEquals($out['file'], new \CURLFile($filepath, mime_content_type($filepath), 'APITESTFILE'));
    }

    // test for runAction
    /**
     * @runInSeparateProcess
     */
    public function test_runAction_CheckNull()
    {
        $this->initialize();

        $apitester = new ApitesterController();
        $ref = new ReflectionClass(get_class($apitester));

        $apitester->runAction();

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($apitester);

        $expect_out = "<pre>\nnull\n</pre>\n";
        $this->assertEquals($out, $expect_out);
    }

    // test for indexAction
}
