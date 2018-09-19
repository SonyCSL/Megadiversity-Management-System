<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\core\Requests;

class RequestsTest extends TestCase
{
    const TEST_ASSOCIATIVE_ARRAY_NAME = 'test';
    const TEST_ASSOCIATIVE_ARRAY_PARAM = 'request_test';

    const TEST_INIT_FILES_NAME = 'test_files.jpg';
    const TEST_INIT_FILES_TYPE = 'image/jpeg';
    const TEST_INIT_FILES_TMP_NAME = 'tmp_test_files.jpg';
    const TEST_INIT_FILES_ERROR = 0;
    const TEST_INIT_FILES_SIZE = 1000000;

    const TEST_FILE_FORM_KEY = 'file1';
    const TEST_FILE_ERROR_MESSAGE = 'Error message';

    /**
     * @doesNotPerformAssertions
     */
    //Initialize preudo $_FILE
    public function InitFiles(String $file_name)
    {
        $file_str[$file_name]['name'] = self::TEST_INIT_FILES_NAME;
        $file_str[$file_name]['type'] = self::TEST_INIT_FILES_TYPE;
        $file_str[$file_name]['tmp_name'] = self::TEST_INIT_FILES_TMP_NAME;
        $file_str[$file_name]['error'] = self::TEST_INIT_FILES_ERROR;
        $file_str[$file_name]['size'] = self::TEST_INIT_FILES_SIZE;

        return $file_str;
    }

    private function assert_ErrorCode(int $error_code, String $message)
    {
        $file_error_str = $this->InitFiles(self::TEST_FILE_FORM_KEY);
        $file_error_str[self::TEST_FILE_FORM_KEY]['error'] = $error_code;
        $error = new Requests(array(), $file_error_str);

        $file_error = $error->get_files_error();

        $this->assertEquals($file_error[self::TEST_FILE_FORM_KEY]['tmp_name'], self::TEST_INIT_FILES_TMP_NAME);
        $this->assertEquals($file_error[self::TEST_FILE_FORM_KEY]['reason'], $message);
    }

    //Test for Constructer
    public function test_construct_CheckRequestCopy()
    {
        $request_str = array('FirSt' => 1, 4 => 'SecOnd', 'ThiRd' => 10);
        $requests = new Requests($request_str);

        $request_ref = array('first' => 1, 4 => 'SecOnd', 'third' => 10);

        $this->assertEquals($requests->get_request(), $request_ref);
    }

    public function test_construct_CheckFileUploader()
    {
        $file_uploader_str = $this->InitFiles(self::TEST_FILE_FORM_KEY);
        $requests = new Requests(array(), $file_uploader_str);

        $file_uploader = $requests->get_files_uploaded();

        $this->assertEquals($file_uploader[self::TEST_FILE_FORM_KEY]['tmp_name'], self::TEST_INIT_FILES_TMP_NAME);
        $this->assertEquals($file_uploader[self::TEST_FILE_FORM_KEY]['name'], self::TEST_INIT_FILES_NAME);
    }

    public function test_construct_CheckFileErrorCode()
    {
        $this->assert_ErrorCode(1, 'The uploaded file exceeds the upload_max_filesize directive in php.ini');
        $this->assert_ErrorCode(2,
            'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
        $this->assert_ErrorCode(3, 'The uploaded file was only partially uploaded');
        $this->assert_ErrorCode(4, 'No file was uploaded');
        $this->assert_ErrorCode(5, 'Unknown upload error'); //Unknown
        $this->assert_ErrorCode(6, 'Missing a temporary folder');
        $this->assert_ErrorCode(7, 'Failed to write file to disk');
        $this->assert_ErrorCode(8, 'File upload stopped by extension');
        $this->assert_ErrorCode(9, 'Unknown upload error'); //Unknown
    }

    public function test_construct_CheckFileSize()
    {
        $file_size_str = $this->InitFiles(self::TEST_FILE_FORM_KEY);
        $file_size_str[self::TEST_FILE_FORM_KEY]['size'] = 0;
        $requests = new Requests(array(), $file_size_str);

        $file_size = $requests->get_files_error();

        $this->assertEquals($file_size[self::TEST_FILE_FORM_KEY]['tmp_name'], self::TEST_INIT_FILES_TMP_NAME);
        $this->assertEquals($file_size[self::TEST_FILE_FORM_KEY]['reason'], 'The uploaded file is empty');
    }

    // Test for get
    public function test_get_KeyNull()
    {
        $requests = new Requests(array('TeSt' => self::TEST_ASSOCIATIVE_ARRAY_PARAM));
        $out = $requests->get();

        $requests_ref = array('test' => self::TEST_ASSOCIATIVE_ARRAY_PARAM);

        $this->assertEquals($out, $requests_ref);
    }

    public function test_get_RequestsNull()
    {
        $requests = new Requests(array());
        $out = $requests->get('hoge');

        $this->assertNull($out);
    }

    public function test_get_RequestsEmpty()
    {
        $requests = new Requests(array(''));
        $out = $requests->get('hoge');

        $this->assertNull($out);
    }

    public function test_get_KeyNullRequestsNull()
    {
        $requests = new Requests(array());
        $out = $requests->get();

        $this->assertEquals($out, array());
    }

    public function test_get_NotSafeCaseKeyExists()
    {
        $requests = new Requests(
            array(
                self::TEST_ASSOCIATIVE_ARRAY_NAME => self::TEST_ASSOCIATIVE_ARRAY_PARAM,
            )
        );
        $out = $requests->get(self::TEST_ASSOCIATIVE_ARRAY_NAME, false);

        $this->assertEquals($out, self::TEST_ASSOCIATIVE_ARRAY_PARAM);
    }

    public function test_get_SafeCaseKeyExistsStringArray()
    {
        $requests = new Requests(
            array(
                self::TEST_ASSOCIATIVE_ARRAY_NAME => self::TEST_ASSOCIATIVE_ARRAY_PARAM,
            )
        );
        $out = $requests->get(self::TEST_ASSOCIATIVE_ARRAY_NAME);

        $this->assertEquals($out, self::TEST_ASSOCIATIVE_ARRAY_PARAM);
    }

    public function test_get_SafeCaseKeyExistsStringArrayWithTag()
    {
        $requests = new Requests(
            array(
                self::TEST_ASSOCIATIVE_ARRAY_NAME => '<a href="#fragment">'.self::TEST_ASSOCIATIVE_ARRAY_PARAM.'</a>',
            )
        );
        $out = $requests->get(self::TEST_ASSOCIATIVE_ARRAY_NAME);

        $this->assertEquals($out, self::TEST_ASSOCIATIVE_ARRAY_PARAM);
    }

    public function test_get_SafeCaseKeyExistsAndUpperStringArray()
    {
        $requests = new Requests(
            array(
                self::TEST_ASSOCIATIVE_ARRAY_NAME => '<a href="#fragment">'.self::TEST_ASSOCIATIVE_ARRAY_PARAM.'</a>',
            )
        );
        $out = $requests->get('TeSt');

        $this->assertEquals($out, self::TEST_ASSOCIATIVE_ARRAY_PARAM);
    }

    public function test_get_SafeCaseKeyExistsUnstringArray()
    {
        $requests = new Requests(
            array(
                self::TEST_ASSOCIATIVE_ARRAY_NAME => 0,
            )
        );
        $out = $requests->get(self::TEST_ASSOCIATIVE_ARRAY_NAME);

        $this->assertEquals($out, 0);
    }

    public function test_get_KeyNotExist()
    {
        $requests = new Requests(
            array(
                self::TEST_ASSOCIATIVE_ARRAY_NAME => self::TEST_ASSOCIATIVE_ARRAY_PARAM,
            )
        );
        $out = $requests->get('hoge');

        $this->assertNull($out);
    }

    public function test_get_KeyEmpty()
    {
        $requests = new Requests(
            array(
                self::TEST_ASSOCIATIVE_ARRAY_NAME => '',
            )
        );
        $out = $requests->get(self::TEST_ASSOCIATIVE_ARRAY_NAME);

        $this->assertNull($out);
    }

    //Test for filename
    public function test_filename_NotEmpty()
    {
        $file_uploaded_str = $this->InitFiles(self::TEST_FILE_FORM_KEY);

        $requests = new Requests(array(), $file_uploaded_str);

        $out = $requests->filename(self::TEST_FILE_FORM_KEY);

        $this->assertEquals($out, self::TEST_INIT_FILES_NAME);
    }

    public function test_filename_Empty()
    {
        $file_uploaded_str = $this->InitFiles(self::TEST_FILE_FORM_KEY);

        $requests = new Requests(array(), $file_uploaded_str);

        $out = $requests->filename('');

        $this->assertEmpty($out);
    }

    public function test_filename_NotMatch()
    {
        $file_uploaded_str = $this->InitFiles(self::TEST_FILE_FORM_KEY);

        $requests = new Requests(array(), $file_uploaded_str);

        $out = $requests->filename('hoge');

        $this->assertEmpty($out);
    }

    //Test for filepath
    public function test_filepath_NotEmpty()
    {
        $file_uploaded_str = $this->InitFiles(self::TEST_FILE_FORM_KEY);

        $requests = new Requests(array(), $file_uploaded_str);

        $out = $requests->filepath(self::TEST_FILE_FORM_KEY);

        $this->assertEquals($out, self::TEST_INIT_FILES_TMP_NAME);
    }

    public function test_filepath_Empty()
    {
        $file_uploaded_str = $this->InitFiles(self::TEST_FILE_FORM_KEY);

        $requests = new Requests(array(), $file_uploaded_str);

        $out = $requests->filepath('');

        $this->assertEmpty($out);
    }

    public function test_filepath_NotMatch()
    {
        $file_uploaded_str = $this->InitFiles(self::TEST_FILE_FORM_KEY);

        $requests = new Requests(array(), $file_uploaded_str);

        $out = $requests->filepath('hoge');

        $this->assertEmpty($out);
    }

    //Test for fileError
    public function test_fileError_NotEmpty()
    {
        $file_error_str = $this->InitFiles(self::TEST_FILE_FORM_KEY);
        $file_error_str[self::TEST_FILE_FORM_KEY]['error'] = 1;

        $requests = new Requests(array(), $file_error_str);

        $out = $requests->fileError(self::TEST_FILE_FORM_KEY);

        $this->assertEquals($out, 'The uploaded file exceeds the upload_max_filesize directive in php.ini');
    }

    public function test_fileError_Empty()
    {
        $file_error_str = $this->InitFiles(self::TEST_FILE_FORM_KEY);
        $file_error_str[self::TEST_FILE_FORM_KEY]['error'] = 1;

        $requests = new Requests(array(), $file_error_str);

        $out = $requests->fileError('');

        $this->assertEmpty($out);
    }

    public function test_fileError_NotMatch()
    {
        $file_error_str = $this->InitFiles(self::TEST_FILE_FORM_KEY);
        $file_error_str[self::TEST_FILE_FORM_KEY]['error'] = 1;

        $requests = new Requests(array(), $file_error_str);

        $out = $requests->fileError('hoge');

        $this->assertEmpty($out);
    }
}
