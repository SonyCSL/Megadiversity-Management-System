<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

require_once __DIR__.'/MariadbTestCase.php'; # alternative TestCase for MongoDB
use artichoke\framework\abstracts\MariadbBase;

class MariadbBaseTest extends MariadbTestCase
{
    private $stub;
    private $reflection;

    public function setUp()
    {
        #MariadbBase::setConnector($this->getTestConnector());
        #$this->stub = $this->getMockForAbstractClass(MariadbBase::class); # abstract -> concrete
        #$this->reflection = new \ReflectionClass($this->stub); # protected -> public
    }

    public function test_constructException()
    {
        // no use setConnector()
        try {
            $stub = $this->getMockForAbstractClass(MariadbBase::class); # abstract -> concrete
            $mariadb = new $stub();
        } catch (Exception $e) {
            $this->assertEquals('Database(\mysqli) has not set', $e->getMessage());
        }
    }

    public function test_Query()
    {
        MariadbBase::setConnector($this->getTestConnector());
        $stub = $this->getMockForAbstractClass(MariadbBase::class); # abstract -> concrete
        $reflection = new \ReflectionClass($stub); # protected -> public
        $Q = $reflection->getMethod('Q');
        $Q->setAccessible(true);

        $this->tableCleanUp('album');
        $this->tableCleanUp('user');

        $this->dbTestInsert('user', [1, 'TESTUSER-ADMIN', 'DUMMY_PASSWD', 'test.admin@localhost', 1, 1, '']);
        $test_record1 = ['1', '2018-01-01 00:00:00', '2018-06-11 23:54:12', '1', 'MY-ALBUM-TITLE!', 'This is mine.', '7', '5', '4'];
        $this->dbTestInsert('album', $test_record1);
        $Q_result1 = $Q->invoke($stub, 'SELECT * FROM album')->fetch_row();
        $this->assertEquals($test_record1, $Q_result1);

        $test_record2 = ['2', 'WOLFMAN', 'JOHNNY', 'tester@localhost', '1', '1', ''];
        $Q_result2 = $Q->invoke($stub, "INSERT into user VALUES(2, 'WOLFMAN', 'JOHNNY', 'tester@localhost', 1, 1, '')");
        $this->assertEquals($test_record2, $this->dbTestSelect('user', 1));
    }
}
