<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\util\ApiResult;

class ApiResultTest extends TestCase
{
    const TEST_GET_SUCCESS = 0;
    const TEST_POST_SUCCESS = 1;
    const TEST_ERROR_INTERNAL = 2;
    const TEST_ACCESSKEY_ISNT_SET = 3;
    const TEST_INVALID_ACCESSKEY = 4;
    const TEST_DEVICE_DISABLED = 5;
    const TEST_HTTP_METHOD_MISMATCH = 6;
    const TEST_ENDPOINT_UNAVAILABLE = 7;
    const TEST_CONTENT_TYPE_UNKNOWN = 8;
    const TEST_POSTED_NOTHING = 9;
    const TEST_REQUIRED_FIELD_ISNT_SET = 10;
    const TEST_DATASTRING_JSON_PARSE_ERROR = 11;
    const TEST_EXPECTED_RESULT_SET = [
        [
            'result_message' => 'Success',
            'result_http_status_code' => 200,
        ], [
            'result_message' => 'Success',
            'result_http_status_code' => 201,
        ], [
            'result_message' => 'Internal model error occurred',
            'result_http_status_code' => 500,
        ], [
            'result_message' => 'X-Api-Accesskey header is not set',
            'result_http_status_code' => 401,
        ], [
            'result_message' => 'Invalid X-Api-Accesskey',
            'result_http_status_code' => 403,
        ], [
            'result_message' => 'Device is set as disabled',
            'result_http_status_code' => 304,
        ], [
            'result_message' => 'HTTP method is not consistent with requested REST-API endpoint',
            'result_http_status_code' => 405,
        ], [
            'result_message' => 'Requested API endpoint is not implemented',
            'result_http_status_code' => 501,
        ], [
            'result_message' => 'Content-Type header is not set',
            'result_http_status_code' => 406,
        ], [
            'result_message' => 'No any valid content is included in the request',
            'result_http_status_code' => 406,
        ], [
            'result_message' => 'At least 1 field required either DATA, DATASTRING or FILE',
            'result_http_status_code' => 406,
        ], [
            'result_message' => 'Datastring is not valid JSON style document (parse error)',
            'result_http_status_code' => 406,
        ],
    ];
    const TEST_EXPECTED_UNKNOWN_ERROR = [
        'code' => -1,
        'result' => 'Unexpected error occured',
    ];
    const TEST_SAMPLE_DETAIL_ARRAYS = [
        [1, 2, 4, 8, 16, 32, 64],
        [
            'analytics_module' => 'SampleModel',
            'result_set' => [
                'temparature' => 21.5,
                'celcius' => true,
            ],
        ],
        [null],
        ['The quick brown fox jumps over the lazy dog.'],
    ];

    public function test_constructor_invalid_arguments_int()
    {
        $api_result1 = new ApiResult(-1);
        $api_result_expected1 = self::TEST_EXPECTED_UNKNOWN_ERROR + ['status' => 'Code: -1'];
        $api_result2 = new ApiResult(20);
        $api_result_expected2 = self::TEST_EXPECTED_UNKNOWN_ERROR + ['status' => 'Code: 20'];
        $api_result3 = new ApiResult(30000);
        $api_result_expected3 = self::TEST_EXPECTED_UNKNOWN_ERROR + ['status' => 'Code: 30000'];

        $this->assertEquals($api_result_expected1, $api_result1->toArray());
        $this->assertEquals($api_result_expected2, $api_result2->toArray());
        $this->assertEquals($api_result_expected3, $api_result3->toArray());
    }

    /**
     * @expectedException TypeError
     */
    public function test_constructor_invalid_argument_types()
    {
        $api_result1 = new ApiResult('hoge');
        $api_result2 = new ApiResult(3.5);
        $api_result3 = new ApiResult(null);
        $api_result4 = new ApiResult(['apple', 'banana', 'orange']);
    }

    public function test_valid_results()
    {
        $api_result_instances[self::TEST_GET_SUCCESS] = new ApiResult(self::TEST_GET_SUCCESS);
        $api_result_instances[self::TEST_POST_SUCCESS] = new ApiResult(self::TEST_POST_SUCCESS);
        $api_result_instances[self::TEST_ERROR_INTERNAL] = new ApiResult(self::TEST_ERROR_INTERNAL);
        $api_result_instances[self::TEST_ACCESSKEY_ISNT_SET] = new ApiResult(self::TEST_ACCESSKEY_ISNT_SET);
        $api_result_instances[self::TEST_INVALID_ACCESSKEY] = new ApiResult(self::TEST_INVALID_ACCESSKEY);
        $api_result_instances[self::TEST_DEVICE_DISABLED] = new ApiResult(self::TEST_DEVICE_DISABLED);
        $api_result_instances[self::TEST_HTTP_METHOD_MISMATCH] = new ApiResult(self::TEST_HTTP_METHOD_MISMATCH);
        $api_result_instances[self::TEST_ENDPOINT_UNAVAILABLE] = new ApiResult(self::TEST_ENDPOINT_UNAVAILABLE);
        $api_result_instances[self::TEST_CONTENT_TYPE_UNKNOWN] = new ApiResult(self::TEST_CONTENT_TYPE_UNKNOWN);
        $api_result_instances[self::TEST_POSTED_NOTHING] = new ApiResult(self::TEST_POSTED_NOTHING);
        $api_result_instances[self::TEST_REQUIRED_FIELD_ISNT_SET] = new ApiResult(self::TEST_REQUIRED_FIELD_ISNT_SET);
        $api_result_instances[self::TEST_DATASTRING_JSON_PARSE_ERROR] = new ApiResult(self::TEST_DATASTRING_JSON_PARSE_ERROR);

        foreach ($api_result_instances as $constructor_argument => $result_instance) {
            // generate expected values
            $expected_result_json = json_encode([
                'code' => (int)$constructor_argument,
                'result' => self::TEST_EXPECTED_RESULT_SET[$constructor_argument]['result_message'],
            ]);
            $expected_http_code = self::TEST_EXPECTED_RESULT_SET[$constructor_argument]['result_http_status_code'];

            // TEST: check json body
            $this->assertEquals($expected_result_json, $result_instance->toJson());

            // TEST: __toString automatic converting to json string
            $this->assertEquals($expected_result_json, (string)$result_instance);

            // TEST: http status code
            $this->assertEquals($expected_http_code, $result_instance->getHttpStatusCode());
        }
    }

    public function test_set_details()
    {
        $constructor_argument_result_code = self::TEST_GET_SUCCESS;
        $result_instance = new ApiResult($constructor_argument_result_code);

        foreach (self::TEST_SAMPLE_DETAIL_ARRAYS as $expected_detail) {
            // generate expected values
            $expected_result_json = json_encode([
                'code' => $constructor_argument_result_code,
                'result' => self::TEST_EXPECTED_RESULT_SET[$constructor_argument_result_code]['result_message'],
                'detail' => $expected_detail,
            ]);
            // set detail
            $result_instance->setDetail($expected_detail);
            // TEST: json with detail array
            $this->assertEquals($expected_result_json, (string)$result_instance);
        }
    }
}
