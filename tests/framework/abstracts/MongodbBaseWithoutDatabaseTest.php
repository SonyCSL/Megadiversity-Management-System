<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

require_once __DIR__.'/MongodbTestCase.php'; # alternative TestCase for MongoDB
use artichoke\framework\abstracts\MongodbBase;

class MongodbBaseWithoutDatabaseTest extends MongodbTestCase
{
    /**
     * @runInSeparateProcess
     * @expectedException Exception
     */
    public function test_constructorExceptionNotSetDatabase()
    {
        // MongodbBase::setDatabase($this->getTestDatabase()); # not set mongodb test database
        $stub = $this->getMockForAbstractClass(MongodbBase::class); # abstract -> concrete
        $reflection = new \ReflectionClass($stub); # protected -> public (make accessible)
    }
}
