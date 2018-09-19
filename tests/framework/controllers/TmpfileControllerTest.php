<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\controllers\TmpfileController;

use artichoke\framework\core\Session;

class TmpfileControllerTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function initialize()
    {
        $_SERVER['SERVER_NAME'] = 'server';
        $_SERVER['SERVER_PROTOCOL'] = 'protocol';
    }

    // test for indexAction
    /**
     * @runInSeparateProcess
     */
    public function test_indexAction_CheckTmpFileExists()
    {
        $this->initialize();

        session_start();
        $_SESSION[Session::SESSKEY]['tmpfile'] = 'data';
        session_commit();

        $tmpfile = new TmpfileController();

        $ref = new ReflectionClass(get_class($tmpfile));

        $tmpfile->indexAction();

        $refVal = $ref->getProperty('resource');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($tmpfile);

        $this->assertEquals($out, 'data');

        $refVal = $ref->getProperty('mode');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($tmpfile);

        $this->assertEquals($out, 1);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_indexAction_argsNotEmpty()
    {
        $this->initialize();

        $tmpfile = new TmpfileController();

        $ref = new ReflectionClass(get_class($tmpfile));

        $tmpfile->indexAction(['arg0', 'arg1', 'tmp']);

        $refVal = $ref->getProperty('mode');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($tmpfile);

        $this->assertEquals($out, 0);
    }
}
