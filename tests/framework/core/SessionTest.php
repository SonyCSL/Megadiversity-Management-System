<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\core\Session;

class SessionTest extends TestCase
{
    const TEST_LOGIN_NAME = 'test_login';
    const TEST_SESSION_TMPFILE_NAME = 'test_session_tmpfile';
    const TEST_SESSION_TMPFILE_LENGTH = 20;
    const TEST_REQUEST_URL = 'https://www.test.html';

    // Test for init
    /**
     * @runInSeparateProcess
     */
    public function test_init()
    {
        (new Session())->init();

        $cache_expire = session_cache_expire();
        $cache_limiter = session_cache_limiter();

        $this->assertEquals($cache_expire, 0);
        $this->assertEquals($cache_limiter, 'nocache');
    }

    // Test for loggingIn
    /**
     * @runInSeparateProcess
     */
    public function test_loggingIn_NameExists()
    {
        (new Session())->loggingIn();

        $now_id = session_id();

        (new Session())->loggingIn(self::TEST_LOGIN_NAME);

        $new_id = session_id();

        $this->assertEquals($_SESSION[Session::SESSKEY]['login_state'], true);
        $this->assertEquals($_SESSION[Session::SESSKEY]['login_name'], self::TEST_LOGIN_NAME);
        $this->assertFalse($now_id === $new_id);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_loggingIn_NameNull()
    {
        (new Session())->loggingIn();

        $this->assertEquals($_SESSION[Session::SESSKEY]['login_state'], true);
        $this->assertEquals($_SESSION[Session::SESSKEY]['login_name'], '');
    }

    // Test for loginStatus
    /**
     * @runInSeparateProcess
     */
    public function test_loginStatus_Alreadylogin()
    {
        // Login
        $session = new Session();
        $session->loggingIn();

        $out = $session->loginStatus();

        $this->assertTrue($out);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_loginStatus_Notlogin()
    {
        // Login
        $session = new Session();

        $out = $session->loginStatus();

        $this->assertFalse($out);
    }

    // Test for getLoginName
    /**
     * @runInSeparateProcess
     */
    public function test_getLoginName_loginNameExists()
    {
        // Login
        $session = new Session();

        $session->loggingIn(self::TEST_LOGIN_NAME);
        $out = $session->getLoginName();

        $this->assertEquals($out, self::TEST_LOGIN_NAME);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getLoginName_loginNameEmpty()
    {
        // Login
        $session = new Session();
        $session->loggingIn();
        $out = $session->getLoginName();

        $this->assertEquals($out, '');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getLoginName_loginNameNull()
    {
        // Login
        $session = new Session();
        $out = $session->getLoginName();

        $this->assertNull($out);
    }

    // Test for loggingOut
    /**
     * @runInSeparateProcess
     */
    public function test_loggingOut()
    {
        // Set session status
        $session = new Session();
        $session->loggingIn(self::TEST_LOGIN_NAME);

        $this->assertTrue(isset($_SESSION[Session::SESSKEY]));

        // Remove session status
        $session->loggingOut();
        $this->assertFalse(isset($_SESSION[Session::SESSKEY]));
    }

    // Test for tmpfile
    /**
     * @runInSeparateProcess
     */
    public function test_tmpfile_CheckDataLength()
    {
        $out = (new Session())->tmpfile(self::TEST_SESSION_TMPFILE_NAME);
        $this->assertEquals($out, self::TEST_SESSION_TMPFILE_LENGTH);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_tmpfile_DataNullTmpFileExists()
    {
        session_start();
        $_SESSION[Session::SESSKEY]['tmpfile'] = self::TEST_SESSION_TMPFILE_NAME;
        session_commit();
        $out = (new Session())->tmpfile();

        $this->assertEquals($out, self::TEST_SESSION_TMPFILE_NAME);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_tmpfile_DataNullTmpFileNull()
    {
        $out = (new Session())->tmpfile();
        $this->assertNull($out);
    }

    // Test for jumpAfterLogin
    /**
     * @runInSeparateProcess
     */
    public function test_jumpAfterLogin_NotLoginUrlNull()
    {
        $session = new Session();

        $out = $session->jumpAfterLogin();

        $this->assertTrue($out);
        $this->assertEquals($_SESSION[Session::SESSKEY]['requested_path'], '');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_jumpAfterLogin_NotLoginUrlExists()
    {
        $session = new Session();

        $out = $session->jumpAfterLogin(self::TEST_LOGIN_NAME);

        $this->assertTrue($out);
        $this->assertEquals($_SESSION[Session::SESSKEY]['requested_path'], self::TEST_LOGIN_NAME);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_jumpAfterLogin_LoginRequestExists()
    {
        $session = new Session();
        $session->loggingIn();

        session_start();
        $_SESSION[Session::SESSKEY]['requested_path'] = self::TEST_REQUEST_URL;
        session_commit();

        $out = $session->jumpAfterLogin();

        $this->assertEquals($out, self::TEST_REQUEST_URL);
        $this->assertFalse(isset($_SESSION[Session::SESSKEY]['requested_path']));
    }

    /**
     * @runInSeparateProcess
     */
    public function test_jumpAfterLogin_LoginRequestNull()
    {
        $session = new Session();
        $session->loggingIn();

        $out = $session->jumpAfterLogin();

        $this->assertFalse($out);
    }

    // Test for destruct
    /**
     * @runInSeparateProcess
     */
    public function test_destruct()
    {
        // Set session status
        $session = new Session();
        $session->loggingIn(self::TEST_LOGIN_NAME);

        $this->assertTrue(isset($_SESSION[Session::SESSKEY]));

        // Remove session status
        $session->destruct();

        $this->assertEquals($_SESSION, array());
    }
}
