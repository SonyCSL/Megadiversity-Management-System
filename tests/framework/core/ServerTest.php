<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\core\Server;

class ServerTest extends TestCase
{
    const TEST_HOST_NAME = 'www.server.com';
    const TEST_SERVER_NAME = 'test.com';
    const TEST_FQDN_STR = 'fqdn';
    const TEST_SERVER_PROTOCOL = 'HTTP/2.0';
    const TEST_HTTP_STATUS_500 = '500 Internal Server Error';
    const TEST_MIME_TYPE_HEAD = 'Content-Type: ';
    const TEST_REQUEST_METHOD = 'REQUEST_METHOD';

    // Tests for myFQDN
    public function testmyFQDN_ReturnServer()
    {
        $server = new Server(array('SERVER_NAME' => '127.0.0.1'));
        $out = $server->myFQDN();
        $this->assertSame($out, gethostname());
    }

    public function testmyFQDN_DontReturnServer()
    {
        $server = new Server(array('SERVER_NAME' => '127.0.0.2'));
        $out = $server->myFQDN();
        $this->assertSame($out, '127.0.0.2');
    }

    public function testmyFQDN_IsNotEqualsServerAndHost()
    {
        $server_str = array(
            'SERVER_NAME' => self::TEST_SERVER_NAME,
            'HTTP_HOST' => self::TEST_HOST_NAME,
        );

        $server = new Server($server_str);
        $out = $server->myFQDN();

        $this->assertSame($out, self::TEST_SERVER_NAME);
    }

    public function testmyFQDN_IsEqualsServerAndHost()
    {
        $server_str = array(
            'SERVER_NAME' => 'server.com',
            'HTTP_HOST' => self::TEST_HOST_NAME,
        );

        $server = new Server($server_str);
        $out = $server->myFQDN();

        $this->assertSame($out, self::TEST_HOST_NAME);
    }

    // Tests for rootURL
    public function testrootURL_WithHttpsAndFqdn()
    {
        $server_str = array('HTTPS' => 'test');
        $server = new Server($server_str);
        $out = $server->rootURL(self::TEST_HOST_NAME);

        $this->assertSame($out, 'https://'.self::TEST_HOST_NAME.'/');
    }

    public function testrootURL_WithHttps()
    {
        $server_str = array(
            'HTTPS' => 'test',
            'SERVER_NAME' => self::TEST_SERVER_NAME,
            'HTTP_HOST' => self::TEST_HOST_NAME,
        );
        $server = new Server($server_str);
        $out = $server->rootURL();

        $this->assertSame($out, 'https://'.$server->myFQDN().'/');
    }

    public function testrootURL_NoHttpsPattern0()
    {
        //Set Null to 'HTTPS'
        $server_str = array(
            'HTTPS' => '',
            'SERVER_NAME' => self::TEST_SERVER_NAME,
            'HTTP_HOST' => self::TEST_HOST_NAME,
        );
        $server = new Server($server_str);
        $out = $server->rootURL(self::TEST_HOST_NAME);

        $this->assertSame($out, 'http://'.self::TEST_HOST_NAME.'/');
    }

    public function testrootURL_NoHttpsPattern1()
    {
        //Unset'HTTPS'
        $server_str = array(
            'SERVER_NAME' => self::TEST_SERVER_NAME,
            'HTTP_HOST' => self::TEST_HOST_NAME,
        );
        $server = new Server($server_str);
        $out = $server->rootURL(self::TEST_HOST_NAME);

        $this->assertSame($out, 'http://'.self::TEST_HOST_NAME.'/');
    }

    //Test for redirect
    /**
     * @runInSeparateProcess
     */
    public function testredirect_SetPage()
    {
        $server_str = array(
            'HTTPS' => 'test',
            'SERVER_NAME' => self::TEST_SERVER_NAME,
            'HTTP_HOST' => self::TEST_HOST_NAME,
        );
        $server = new Server($server_str);
        $test_url = $server->rootURL();

        $out = $server->redirect('redirect');

        $this->assertSame($out, $test_url.'redirect');
    }

    /**
     * @runInSeparateProcess
     */
    public function testredirect_NotSetPage()
    {
        $server_str = array(
            'HTTPS' => 'test',
            'SERVER_NAME' => self::TEST_SERVER_NAME,
            'HTTP_HOST' => self::TEST_HOST_NAME,
        );
        $server = new Server($server_str);
        $out = $server->redirect();

        $test_url = $server->rootURL();

        $this->assertSame($out, $test_url);
    }

    //Test for switchFQDN
    /**
     * @runInSeparateProcess
     */
    public function testswitchFQDN_SetFqdn()
    {
        $server_str = array(
            'HTTPS' => 'test',
            'SERVER_NAME' => self::TEST_SERVER_NAME,
            'HTTP_HOST' => self::TEST_HOST_NAME,
        );
        $server = new Server($server_str);
        $out = $server->switchFQDN(self::TEST_FQDN_STR);

        $test_url = $server->rootURL(self::TEST_FQDN_STR);

        $this->assertSame($out, $test_url);
    }

    /**
     * @runInSeparateProcess
     */
    public function testswitchFQDN_UnsetFqdn()
    {
        $server_str = array(
            'HTTPS' => 'test',
            'SERVER_NAME' => self::TEST_SERVER_NAME,
            'HTTP_HOST' => self::TEST_HOST_NAME,
        );
        $server = new Server($server_str);
        $out = $server->switchFQDN();

        $test_url = $server->rootURL();

        $this->assertSame($out, $test_url);
    }

    //Test for sendHttpStatusCode
    /**
     * @runInSeparateProcess
     */
    public function testsendHttpStatusCode_StatusExists()
    {
        $server_str = array(
            'SERVER_PROTOCOL' => self::TEST_SERVER_PROTOCOL,
        );
        $server = new Server($server_str);
        $out = $server->sendHttpStatusCode(200);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'200 OK');

        $out = $server->sendHttpStatusCode(201);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'201 Created');

        $out = $server->sendHttpStatusCode(300);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'300 Multiple Choices');

        $out = $server->sendHttpStatusCode(400);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'400 Bad Request');

        $out = $server->sendHttpStatusCode(403);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'403 Forbidden');

        $out = $server->sendHttpStatusCode(404);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'404 Not Found');

        $out = $server->sendHttpStatusCode(405);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'405 Method Not Allowed');

        $out = $server->sendHttpStatusCode(406);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'406 Not Acceptable');

        $out = $server->sendHttpStatusCode(408);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'408 Request Timeout');

        $out = $server->sendHttpStatusCode(409);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'409 Conflict');

        $out = $server->sendHttpStatusCode(412);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'412 Precondition Failed');

        $out = $server->sendHttpStatusCode(413);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'413 Request Entity Too Large');

        $out = $server->sendHttpStatusCode(416);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'416 Requested Range Not Satisfiable');

        $out = $server->sendHttpStatusCode(417);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'417 Expectation Failed');

        $out = $server->sendHttpStatusCode(418);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '."418 I'm a teapot");

        $out = $server->sendHttpStatusCode(500);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'500 Internal Server Error');

        $out = $server->sendHttpStatusCode(501);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'501 Not Implemented');

        $out = $server->sendHttpStatusCode(503);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.'503 Service Unavailable');
    }

    /**
     * @runInSeparateProcess
     */
    public function testsendHttpStatusCode_StatusNotExists()
    {
        $server_str = array(
            'SERVER_PROTOCOL' => self::TEST_SERVER_PROTOCOL,
        );
        $server = new Server($server_str);
        $out = $server->sendHttpStatusCode(199);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.self::TEST_HTTP_STATUS_500);

        $out = $server->sendHttpStatusCode(202);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.self::TEST_HTTP_STATUS_500);

        $out = $server->sendHttpStatusCode(301);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.self::TEST_HTTP_STATUS_500);

        $out = $server->sendHttpStatusCode(402);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.self::TEST_HTTP_STATUS_500);

        $out = $server->sendHttpStatusCode(407);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.self::TEST_HTTP_STATUS_500);

        $out = $server->sendHttpStatusCode(410);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.self::TEST_HTTP_STATUS_500);

        $out = $server->sendHttpStatusCode(411);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.self::TEST_HTTP_STATUS_500);

        $out = $server->sendHttpStatusCode(414);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.self::TEST_HTTP_STATUS_500);

        $out = $server->sendHttpStatusCode(415);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.self::TEST_HTTP_STATUS_500);

        $out = $server->sendHttpStatusCode(419);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.self::TEST_HTTP_STATUS_500);

        $out = $server->sendHttpStatusCode(502);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.self::TEST_HTTP_STATUS_500);

        $out = $server->sendHttpStatusCode(504);
        $this->assertSame($out, self::TEST_SERVER_PROTOCOL.' '.self::TEST_HTTP_STATUS_500);
    }

    //Test for sendMimeType
    /**
     * @runInSeparateProcess
     */
    public function testsendMimeType_CheckType()
    {
        $server = new Server(array(''));
        $out = $server->sendMimeType('html');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'text/html; charset=UTF-8');

        $out = $server->sendMimeType('css');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'text/css; charset=UTF-8');

        $out = $server->sendMimeType('js');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'text/javascript; charset=UTF-8');

        $out = $server->sendMimeType('txt');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'text/plain; charset=UTF-8');

        $out = $server->sendMimeType('text');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'text/plain; charset=UTF-8');

        $out = $server->sendMimeType('csv');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'text/csv; charset=UTF-8');

        $out = $server->sendMimeType('json');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/json');

        $out = $server->sendMimeType('gexf');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/gexf+xml');

        $out = $server->sendMimeType('m3u8');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/x-mpegURL');

        $out = $server->sendMimeType('bin');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/octet-stream');

        $out = $server->sendMimeType('zip');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/zip');

        $out = $server->sendMimeType('xml');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/xml');

        $out = $server->sendMimeType('jpg');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'image/jpeg');

        $out = $server->sendMimeType('jpeg');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'image/jpeg');

        $out = $server->sendMimeType('png');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'image/png');

        $out = $server->sendMimeType('gif');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'image/gif');

        $out = $server->sendMimeType('bmp');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'image/bmp');

        $out = $server->sendMimeType('svg');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'image/svg+xml');

        $out = $server->sendMimeType('ts');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'video/mp2t');
    }

    /**
     * @runInSeparateProcess
     */
    public function testsendMimeType_CheckUnknownType()
    {
        $server = new Server(array(''));
        $out = $server->sendMimeType('');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/octet-stream');
    }

    /**
     * @runInSeparateProcess
     */
    public function testsendMimeType_CheckFullSchemeType()
    {
        $server = new Server(array(''));
        $out = $server->sendMimeType('text/html');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'text/html; charset=UTF-8');

        $out = $server->sendMimeType('text/css');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'text/css; charset=UTF-8');

        $out = $server->sendMimeType('text/javascript');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'text/javascript; charset=UTF-8');

        $out = $server->sendMimeType('text/plain');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'text/plain; charset=UTF-8');

        $out = $server->sendMimeType('text/plain');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'text/plain; charset=UTF-8');

        $out = $server->sendMimeType('text/csv');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'text/csv; charset=UTF-8');

        $out = $server->sendMimeType('application/json');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/json');

        $out = $server->sendMimeType('application/gexf+xml');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/gexf+xml');

        $out = $server->sendMimeType('application/x-mpegURL');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/x-mpegURL');

        $out = $server->sendMimeType('application/octet-stream');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/octet-stream');

        $out = $server->sendMimeType('application/zip');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/zip');

        $out = $server->sendMimeType('application/xml');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'application/xml');

        $out = $server->sendMimeType('image/jpeg');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'image/jpeg');

        $out = $server->sendMimeType('image/png');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'image/png');

        $out = $server->sendMimeType('image/gif');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'image/gif');

        $out = $server->sendMimeType('image/bmp');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'image/bmp');

        $out = $server->sendMimeType('image/svg+xml');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'image/svg+xml');

        $out = $server->sendMimeType('video/mp2t');
        $this->assertSame($out, self::TEST_MIME_TYPE_HEAD.'video/mp2t');
    }

    //Test for fromAjax
    public function testfromAjax_AjaxAccessTest()
    {
        $server_str = array(
            'HTTP_X_REQUESTED_WITH' => 'xmlhttprequest',
        );
        $server = new Server($server_str);
        $this->assertTrue($server->fromAjax());

        $server_str = array(
            'HTTP_X_REQUESTED_WITH' => 'xmlHttpRequest',
        );
        $server2 = new Server($server_str);
        $this->assertTrue($server2->fromAjax());
    }

    public function testfromAjax_AjaxNotSet()
    {
        $server_str = array(
            'HTTP_X_REQUESTED_WITH' => '',
        );
        $server = new Server($server_str);
        $this->assertFalse($server->fromAjax());
    }

    public function testfromAjax_AjaxNull()
    {
        $server = new Server(array(''));
        $this->assertFalse($server->fromAjax());
    }

    //Test for currentProtocol
    public function testcurrentProtocol_Case80()
    {
        $server_str = array(
            'SERVER_PORT' => '80',
            'REQUEST_METHOD' => 'request',
        );
        $server = new Server($server_str);
        $out = $server->currentProtocol();

        $this->assertSame($out, 'HTTP_request');

        $server_str = array(
            'SERVER_PORT' => 80,
            'REQUEST_METHOD' => 'request',
        );
        $server = new Server($server_str);
        $out = $server->currentProtocol();

        $this->assertSame($out, 'HTTP_request');
    }

    public function testcurrentProtocol_Case443()
    {
        $server_str = array(
            'SERVER_PORT' => '443',
            'REQUEST_METHOD' => 'request',
        );
        $server = new Server($server_str);
        $out = $server->currentProtocol();

        $this->assertSame($out, 'HTTPS_request');

        $server_str = array(
            'SERVER_PORT' => 443,
            'REQUEST_METHOD' => 'request',
        );
        $server = new Server($server_str);
        $out = $server->currentProtocol();

        $this->assertSame($out, 'HTTPS_request');
    }

    public function testcurrentProtocol_CaseUnknown()
    {
        $server_str = array(
            'SERVER_PORT' => '81',
            'REQUEST_METHOD' => 'request',
        );
        $server = new Server($server_str);
        $out = $server->currentProtocol();

        $this->assertSame($out, 'UNKNOWN');

        $server_str = array(
            'SERVER_PORT' => '444',
            'REQUEST_METHOD' => 'request',
        );
        $server = new Server($server_str);
        $out = $server->currentProtocol();

        $this->assertSame($out, 'UNKNOWN');
    }

    //Test for sendData
    /**
     * @runInSeparateProcess
     */
    public function testsendData_DownloadTrue()
    {
        $out = (new Server(array()))->sendData('test', 'data.txt', 'text', true);

        $ref = xdebug_get_headers();

        $this->assertEquals($ref[0], 'Content-Type: application/force-download');
        $this->assertEquals($ref[1], 'Content-Disposition: attachment; filename="data.txt"');
        $this->assertEquals($ref[2], 'Content-Length: 4');

        $this->assertEquals($out, 4);
    }

    /**
     * @runInSeparateProcess
     */
    public function testsendData_DownloadFalse()
    {
        $out = (new Server(array()))->sendData('tests', 'data.txt', 'text', false);

        $ref = xdebug_get_headers();

        $this->assertEquals('X-Frame-Options: DENY', $ref[0]);
        $this->assertEquals('X-Content-Type-Options : nosniff', $ref[1]);
        $this->assertEquals('X-XSS-Protection: 1; mode=block', $ref[2]);
        $this->assertEquals('Cache-Control: private, no-cache, must-revalidate', $ref[3]);
        $this->assertEquals('Content-Type: text/plain; charset=UTF-8', $ref[4]);
        $this->assertEquals($out, 5);
    }

    /**
     * @runInSeparateProcess
     */
    public function testsendData_DataNull()
    {
        $out = (new Server(array()))->sendData('', 'data.txt', 'text', false);
        $this->assertEquals($out, 0);
    }

    //Test for getAllHeasers
    public function testgetAllHeaders_Check()
    {
        $server_str = array(
            'HTTP_ACCEPT' => 'accept',
            'HTTP_ACCEPT_CHARSET' => 'accept_charset',
            'HTTP_ACCEPT CHARSET' => 'accept_charset2',
            'HTTP_ACCEPT_ENCODING' => 'accept_encoding',
            'HTTP_ACCEPT encoding test' => 'accept_encoding_test',
            'HTTP_ACCEPT_LANGUAGE' => 'accept_language',
            'SERVER_NAME' => 'server',
            'HTTP_CONNECTION' => 'connection',
            'HTTP_HOST' => 'host',
            'HTTP_REFERER' => 'referer',
            'HTTP_USER_AGENT' => 'user_agent',
        );

        $server = new Server($server_str);
        $out = $server->getAllHeaders();

        $server_ref = array(
            'Accept' => 'accept',
            'Accept-Charset' => 'accept_charset',
            'Accept-Charset' => 'accept_charset2',
            'Accept-Encoding' => 'accept_encoding',
            'Accept-Encoding-Test' => 'accept_encoding_test',
            'Accept-Language' => 'accept_language',
            'Connection' => 'connection',
            'Host' => 'host',
            'Referer' => 'referer',
            'User-Agent' => 'user_agent',
        );

        $this->assertSame($out, $server_ref);
    }

    //Test for methodIs
    public function testmethodIs_SetNotNull()
    {
        $server = new Server(array('REQUEST_METHOD' => self::TEST_REQUEST_METHOD));
        $out = $server->methodIs(self::TEST_REQUEST_METHOD);

        $this->assertTrue($out);

        $out = $server->methodIs('Request_Method');

        $this->assertTrue($out);
    }

    public function testmethodIs_SetNull()
    {
        $server = new Server(array('REQUEST_METHOD' => self::TEST_REQUEST_METHOD));
        $out = $server->methodIs();

        $this->assertSame($out, self::TEST_REQUEST_METHOD);
    }
}
