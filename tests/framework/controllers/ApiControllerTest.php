<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\controllers\ApiController;

use artichoke\framework\core\Configurator;
use artichoke\framework\core\Requests;
use artichoke\framework\util\ApiResult;
use artichoke\framework\models\client\Device;
use artichoke\framework\models\entry\Entry;

require_once dirname(dirname(__DIR__)).'/common/GenRootDir.php';

class ApiControllerTest extends TestCase
{
    private $root;

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
        $_SERVER['SERVER_PROTOCOL'] = 'protocol';
    }

    /**
     * @doesNotPerformAssertions
     */
    public function setServerHeaders()
    {
        $_SERVER['HTTP_X_API_ACCESSKEY'] = 'accesskey';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * @doesNotPerformAssertions
     */
    public function setHeadersForPost()
    {
        $_SERVER['HTTP_X_API_ACCESSKEY'] = 'accesskey';
        $_SERVER['HTTP_CONTENT_TYPE'] = 'text/json';
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    /**
     * @doesNotPerformAssertions
     */
    public function genDeviceMock()
    {
        $observer = $this->getMockBuilder(Device::class)
                         ->disableOriginalConstructor()
                         ->setMethods(['exists', 'available', '__destruct'])
                         ->getMock();

        $observer->expects($this->once())
                 ->method('exists')
                 ->willReturn(true);

        $observer->expects($this->once())
                 ->method('available')
                 ->willReturn(true);

        $observer->expects($this->any())
                 ->method('__destruct')
                 ->willReturn(null);

        return $observer;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_getPostActionSkipParam($testClass)
    {
        $setter = function (Requests $request, array $headers, $method) {
            return $this->getPostActionSkipParam($request, $headers, $method);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_setGeoInformation($testClass)
    {
        $setter = function (Requests $request, array $geoJsonArrayOrg) {
            return $this->setGeoInformation($request, $geoJsonArrayOrg);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_setUploaderFromRequests($testClass)
    {
        $setter = function (Requests $request, Entry $uploader) {
            return $this->setUploaderFromRequests($request, $uploader);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_getDataInstance($testClass)
    {
        $setter = function (array $headers, Requests $request) {
            return $this->getDataInstance($headers, $request);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_setUploaderFromFile($testClass)
    {
        $setter = function (Requests $request, Entry $uploader) {
            return $this->setUploaderFromFile($request, $uploader);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    // test for indexAction
    /**
     * @runInSeparateProcess
     */
    public function test_indexAction_InputNull()
    {
        $this->initialize();
        $api = new ApiController();

        $ref = new ReflectionClass(get_class($api));

        $api->indexAction();

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($api);

        $result = $out->getAllParams();

        $this->assertEquals($result['resultJson']['code'], ApiResult::ENDPOINT_UNAVAILABLE);
        $this->assertEquals($result['resultJson']['result'], 'Requested API endpoint is not implemented');
        $this->assertEquals($result['resultJson']['detail'], []);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_indexAction_InputSomething()
    {
        $this->initialize();
        $api = new ApiController();

        $ref = new ReflectionClass(get_class($api));

        $args = ['testin'];

        $api->indexAction($args);

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($api);

        $result = $out->getAllParams();

        $this->assertEquals($result['resultJson']['detail'], $args);
    }

    // test for getAction
    /**
     * @runInSeparateProcess
     */
    public function test_getAction_ResultExists()
    {
        $this->initialize();
        $api = new ApiController();

        $ref = new ReflectionClass(get_class($api));

        // First, set 'result'
        $api->indexAction();

        // do nothing
        $api->getAction();

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($api);

        $result = $out->getAllParams();

        //Equals to output of indexAction
        $this->assertEquals($result['resultJson']['code'], ApiResult::ENDPOINT_UNAVAILABLE);
        $this->assertEquals($result['resultJson']['result'], 'Requested API endpoint is not implemented');
        $this->assertEquals($result['resultJson']['detail'], []);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getAction_MethodIsGet()
    {
        $this->initialize();

        $_SERVER['HTTP_X_API_ACCESSKEY'] = 'accesskey';
        $_SERVER['REQUEST_METHOD'] = 'set';

        $api = new ApiController($this->genDeviceMock());

        $ref = new ReflectionClass(get_class($api));

        // do nothing
        $api->getAction();

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($api);

        $result = $out->getAllParams();

        $this->assertEquals($result['resultJson']['code'], ApiResult::HTTP_METHOD_MISMATCH);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getAction_EndPointFileArgsNotEmptyRunFileController()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $ref = new ReflectionClass(get_class($api));

        $api->getAction(['file', 'test']);

        $refVal = $ref->getProperty('respond_at_destructor');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($api);

        $this->assertFalse($out);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getAction_EndPointFileArgsNotEmpty()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $ref = new ReflectionClass(get_class($api));

        $api->getAction(['file']);

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($api);

        $result = $out->getAllParams();

        $this->assertEquals($result['resultJson']['code'], ApiResult::ERROR_INTERNAL);
        $this->assertEquals($result['resultJson']['status'], 'File ID is required');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getAction_EndPointNotFileArgsNotEmpty()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $ref = new ReflectionClass(get_class($api));

        $api->getAction(['not file', 'test param']);

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($api);

        $result = $out->getAllParams();

        $this->assertEquals($result['resultJson']['code'], ApiResult::ENDPOINT_UNAVAILABLE);
        $this->assertEquals($result['resultJson']['detail'], ['test param']);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getAction_EndPointEmpty()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $ref = new ReflectionClass(get_class($api));

        $api->getAction();

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($api);

        $result = $out->getAllParams();

        $this->assertEquals($result['resultJson']['code'], ApiResult::ENDPOINT_UNAVAILABLE);
    }

    // test for getPostActionSkipParam
    /**
     * @runInSeparateProcess
     */
    public function test_getPostActionSkipParam_MethodFalse()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $request = new Requests([], []);
        $method = false;
        $out = $this->bound_getPostActionSkipParam($api)($request, [], $method);

        $this->assertEquals($out, ApiResult::HTTP_METHOD_MISMATCH);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getPostActionSkipParam_HeaderEmpty()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $request = new Requests([], []);
        $headers = ['null'];
        $method = true;
        $out = $this->bound_getPostActionSkipParam($api)($request, $headers, $method);

        $this->assertEquals($out, ApiResult::CONTENT_TYPE_UNKNOWN);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getPostActionSkipParam_ContentTypeApplicationEmpty()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $request = new Requests([], []);
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $method = true;
        $out = $this->bound_getPostActionSkipParam($api)($request, $headers, $method);

        $this->assertEquals($out, ApiResult::POSTED_NOTHING);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getPostActionSkipParam_ContentTypeApplicationJson()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $request = new Requests([], []);
        $headers = ['Content-Type' => 'application/json'];
        $method = true;
        $out = $this->bound_getPostActionSkipParam($api)($request, $headers, $method);

        $this->assertEquals($out, ApiController::IS_POST_ACTION_NOT_SKIP);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getPostActionSkipParam_ContentTypeTextJson()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $request = new Requests([], []);
        $headers = ['Content-Type' => 'text/json'];
        $method = true;
        $out = $this->bound_getPostActionSkipParam($api)($request, $headers, $method);

        $this->assertEquals($out, ApiController::IS_POST_ACTION_NOT_SKIP);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getPostActionSkipParam_ContentTypeApplicationNotEmpty()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $request = new Requests(['datastring' => 'exists'], []);
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $method = true;
        $out = $this->bound_getPostActionSkipParam($api)($request, $headers, $method);

        $this->assertEquals($out, ApiController::IS_POST_ACTION_NOT_SKIP);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getPostActionSkipParam_ContentTypeOthersFileNull()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $request = new Requests([], []);
        $headers = ['Content-Type' => 'others'];
        $method = true;
        $out = $this->bound_getPostActionSkipParam($api)($request, $headers, $method);

        $this->assertEquals($out, ApiResult::REQUIRED_FIELD_ISNT_SET);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getPostActionSkipParam_ContentTypeOthersFileNotNull()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $request = new Requests(['datastring' => 'exists'], []);
        $headers = ['Content-Type' => 'others'];
        $method = true;
        $out = $this->bound_getPostActionSkipParam($api)($request, $headers, $method);

        $this->assertEquals($out, ApiController::IS_POST_ACTION_NOT_SKIP);
    }

    // test for setGeoInformation
    /**
     * @runInSeparateProcess
     */
    public function test_setGeoInformation_LocationNullGeoInformationNotMatch()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());
        $request = new Requests([], []);

        $out = $this->bound_setGeoInformation($api)($request, ['geo']);

        $this->assertEquals($out[0], ['geo']);
        $this->assertFalse($out[1]);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_setGeoInformation_LocationExistsNoPositionGeoInformationNotMatch()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());
        $request = new Requests(['location' => 'default'], []);

        $out = $this->bound_setGeoInformation($api)($request, []);

        $this->assertEquals($out[0], []);
        $this->assertTrue($out[1]);

        $api = new ApiController($this->genDeviceMock());
        $request = new Requests(['location' => 'on'], []);

        $out = $this->bound_setGeoInformation($api)($request, ['geo', 'info']);

        $this->assertEquals($out[0], ['geo', 'info']);
        $this->assertTrue($out[1]);

        $api = new ApiController($this->genDeviceMock());
        $request = new Requests(['location' => 'true'], []);

        $out = $this->bound_setGeoInformation($api)($request, ['geo']);

        $this->assertEquals($out[0], ['geo']);
        $this->assertTrue($out[1]);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_setGeoInformation_LocationExistsPositionExistsGeoInformationNotMatch()
    {
        $this->initialize();
        $this->setServerHeaders();

        $pos1 = 0.5;
        $pos2 = 99.9;
        $api = new ApiController($this->genDeviceMock());
        $request = new Requests(['location' => $pos1.','.$pos2], []);

        $out = $this->bound_setGeoInformation($api)($request, ['geo']);

        $res = [
            'geo',
            'coordinates' => [$pos1, $pos2],
        ];

        $this->assertEquals($out[0], $res);
        $this->assertTrue($out[1]);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_setGeoInformation_LocationExistsPositionExistsGeoInformationMatch()
    {
        $this->initialize();
        $this->setServerHeaders();

        $pos1 = 0.5;
        $pos2 = 99.9;
        $api = new ApiController($this->genDeviceMock());
        $request = new Requests(
            [
                'location' => $pos1.','.$pos2,
                'altitude' => '8.5',
                'ground_height' => '10',
                'place' => 'japan',
                'local_position' => 'tokyo',
            ],
            []
        );

        $out = $this->bound_setGeoInformation($api)($request, ['geo']);

        $res = [
            'geo',
            'coordinates' => [$pos1, $pos2],
            'altitude' => 8.5,
            'ground_height' => 10,
            'place' => 'japan',
            'local_position' => 'tokyo',
        ];

        $this->assertEquals($out[0], $res);
        $this->assertTrue($out[1]);
    }

    // test for setUploaderFromRequests
    /**
     * @runInSeparateProcess
     */
    public function test_setUploaderFromRequests()
    {
        $this->initialize();
        $this->setServerHeaders();

        $timezone = 9.00;
        $request = new Requests(
            [
                'timezone' => $timezone,
                'tag' => 'tag0,tag1,tag2',
                'public' => true,
                'comment' => 'comment',
            ]
        );
        $entry = new Entry();

        $api = new ApiController($this->genDeviceMock());
        $out = $this->bound_setUploaderFromRequests($api)($request, $entry);

        $ref = new ReflectionClass(get_class($out));

        $refVal = $ref->getProperty('document');
        $refVal->setAccessible(true);
        $doc = $refVal->getValue($out);

        $this->assertEquals($doc['timezone'], $timezone);
        $this->assertEquals($doc['tags'], ['tag0', 'tag1', 'tag2']);
        $this->assertTrue($doc['public']);
        $this->assertEquals($doc['comment'], 'comment');

        // check tags only
        $request = new Requests(
            [
                'tags' => 'tag3,tag4,tag5',
            ]
        );
        $out2 = $this->bound_setUploaderFromRequests($api)($request, $entry);

        $ref = new ReflectionClass(get_class($out2));

        $refVal = $ref->getProperty('document');
        $refVal->setAccessible(true);
        $doc = $refVal->getValue($out2);

        $this->assertEquals($doc['tags'], ['tag3', 'tag4', 'tag5']);
    }

    // test for getDataInstance
    /**
     * @runInSeparateProcess
     */
    public function test_getDataInstance_Null()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $request = new Requests([], []);

        $out = $this->bound_getDataInstance($api)([], $request);

        $this->assertNull($out);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getDataInstance_ContentTypeJson()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $headers = ['Content-Type' => 'application/json'];
        $request = new Requests([], []);

        $out = $this->bound_getDataInstance($api)($headers, $request);

        $ref = new ReflectionClass(get_class($out));

        $refVal = $ref->getProperty('data');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($out);

        $this->assertEquals($res, []);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getDataInstance_DataStringNotNull()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $headers = [];
        $request = new Requests(
            [
                'datastring' => '{"a":1,"b":2,"c":3,"d":4,"e":5}',
            ],
            []
        );

        $out = $this->bound_getDataInstance($api)($headers, $request);

        $ref = new ReflectionClass(get_class($out));

        $refVal = $ref->getProperty('data');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($out);

        $expected_out = [
            'a' => '1',
            'b' => '2',
            'c' => '3',
            'd' => '4',
            'e' => '5',
        ];
        $this->assertEquals($res, $expected_out);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getDataInstance_DataNotNull()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $headers = [];
        $request = new Requests(
            [
                'data' => '{"a":1,"b":2,"c":3,"d":4,"e":5}',
            ],
            []
        );

        $out = $this->bound_getDataInstance($api)($headers, $request);

        $ref = new ReflectionClass(get_class($out));

        $refVal = $ref->getProperty('data');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($out);

        $expected_out = [
            'a' => '1',
            'b' => '2',
            'c' => '3',
            'd' => '4',
            'e' => '5',
        ];
        $this->assertEquals($res, $expected_out);
    }

    // test for setUploaderFromFile
    /**
     * @runInSeparateProcess
     */
    public function test_setUploaderFromFile_FilePathExists()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $entry = new Entry();
        $request = new Requests([], ['file' => ['tmp_name' => $this->root.'/tests/framework/models/entry/sample1.jpg']]);

        $out = $this->bound_setUploaderFromFile($api)($request, $entry);

        $ref = new ReflectionClass(get_class($out));

        $refVal = $ref->getProperty('document');
        $refVal->setAccessible(true);
        $doc = $refVal->getValue($out);

        // check meta (1 element)
        $this->assertEquals($doc['meta']['FILE']['FileSize'], 11866);

        //check geo
        $this->assertEquals($doc['geo']['type'], 'Point');

        //check comment
        $this->assertEquals($doc['comment'], "\nOLYMPUS DIGITAL CAMERA         ");
    }

    // test for postAction
    /**
     * @runInSeparateProcess
     */
    public function test_postAction_CheckResultAlreadySet()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        // set result
        $api->indexAction();
        $api->postAction();

        $ref = new ReflectionClass(get_class($api));

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($api);

        $out = $res->getAllParams();

        $this->assertEquals($out['resultCode'], ApiResult::ENDPOINT_UNAVAILABLE);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_postAction_CheckPostActionSkip()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        // set result
        $api->postAction();

        $ref = new ReflectionClass(get_class($api));

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($api);

        $out = $res->getAllParams();

        $this->assertEquals($out['resultCode'], ApiResult::HTTP_METHOD_MISMATCH);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_postAction_CheckDataInstanceNull()
    {
        $this->initialize();
        $this->setHeadersForPost();

        $observer = $this->getMockBuilder(Device::class)
                         ->disableOriginalConstructor()
                         ->setMethods(['exists', 'available', 'getGeoJsonArray', '__destruct'])
                         ->getMock();

        $observer->expects($this->once())
                 ->method('exists')
                 ->willReturn(true);

        $observer->expects($this->once())
                 ->method('available')
                 ->willReturn(true);

        $observer->expects($this->once())
                 ->method('getGeoJsonArray')
                 ->willReturn([]);

        $observer->expects($this->any())
                 ->method('__destruct')
                 ->willReturn(null);

        $_REQUEST['location'] = 'default';
        $api = new ApiController($observer);

        // set result
        $api->postAction();

        $ref = new ReflectionClass(get_class($api));

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($api);

        $out = $res->getAllParams();

        $this->assertEquals($out['resultCode'], ApiResult::DATASTRING_JSON_PARSE_ERROR);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_postAction_DataCreateFail()
    {
        $this->initialize();
        $this->setHeadersForPost();

        $_SERVER['HTTP_CONTENT_TYPE'] = 'content';
        $_SERVER['SERVER_PORT'] = '9999';
        $_REQUEST = array('datastring' => '{"a":1,"b":2,"c":3,"d":4,"e":5}');

        $observer = $this->getMockBuilder(Device::class)
                         ->disableOriginalConstructor()
                         ->setMethods(['exists', 'available', 'getGeoJsonArray', '__destruct'])
                         ->getMock();

        $observer->expects($this->once())
                 ->method('exists')
                 ->willReturn(true);

        $observer->expects($this->once())
                 ->method('available')
                 ->willReturn(true);

        $observer->expects($this->once())
                 ->method('getGeoJsonArray')
                 ->willReturn([]);

        $observer->expects($this->any())
                 ->method('__destruct')
                 ->willReturn(null);

        $_REQUEST['location'] = 'default';
        $api = new ApiController($observer);

        // set result
        $api->postAction();

        $ref = new ReflectionClass(get_class($api));

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($api);

        $out = $res->getAllParams();

        $this->assertEquals($out['resultCode'], ApiResult::ERROR_INTERNAL);
    }

    // test for searchAction
    /**
     * @runInSeparateProcess
     */
    public function test_searchAction_ResultAlreadySet()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $api->indexAction();
        $api->searchAction();

        $ref = new ReflectionClass(get_class($api));

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($api);

        $out = $res->getAllParams();

        $this->assertEquals($out['resultCode'], ApiResult::ENDPOINT_UNAVAILABLE);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_searchAction_ServerMethodIsNotGet()
    {
        $this->initialize();
        $this->setServerHeaders();

        $_SERVER['REQUEST_METHOD'] = 'SET';

        $api = new ApiController($this->genDeviceMock());

        $api->searchAction();

        $ref = new ReflectionClass(get_class($api));

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($api);

        $out = $res->getAllParams();

        $this->assertEquals($out['resultCode'], ApiResult::HTTP_METHOD_MISMATCH);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_searchAction_ServerMethodIsGet()
    {
        $this->initialize();
        $this->setServerHeaders();

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $api = new ApiController($this->genDeviceMock());

        $api->searchAction();

        $ref = new ReflectionClass(get_class($api));

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($api);

        $out = $res->getAllParams();

        $this->assertEquals($out['resultCode'], ApiResult::ENDPOINT_UNAVAILABLE);
    }

    // test for AnalyticsAction
    /**
     * @runInSeparateProcess
     */
    public function test_analyticsAction_ResultAlreadySet()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $api->indexAction();
        $api->analyticsAction();

        $ref = new ReflectionClass(get_class($api));

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($api);

        $out = $res->getAllParams();

        $this->assertEquals($out['resultCode'], ApiResult::ENDPOINT_UNAVAILABLE);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_analyticsAction_MethodIsNotPost()
    {
        $this->initialize();
        $this->setServerHeaders();

        $api = new ApiController($this->genDeviceMock());

        $api->analyticsAction();

        $ref = new ReflectionClass(get_class($api));

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($api);

        $out = $res->getAllParams();

        $this->assertEquals($out['resultCode'], ApiResult::HTTP_METHOD_MISMATCH);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_analyticsAction_AnalyticsModelNotExists()
    {
        $this->initialize();
        $this->setServerHeaders();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $api = new ApiController($this->genDeviceMock());

        $api->analyticsAction(['test']);

        $ref = new ReflectionClass(get_class($api));

        $refVal = $ref->getProperty('result');
        $refVal->setAccessible(true);
        $res = $refVal->getValue($api);

        $out = $res->getAllParams();

        $this->assertEquals($out['resultCode'], ApiResult::ENDPOINT_UNAVAILABLE);
    }
}
