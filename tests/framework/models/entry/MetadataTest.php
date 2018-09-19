<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\models\entry\Metadata;

class MetadataTest extends TestCase
{
    private $testMetadataInstance1;
    private $testMetadataInstance2;
    private $testMetadataInstance3;
    private $testMetadataInstance4;

    public function setUp()
    {
        // stream
        $stream = tmpfile();
        fwrite($stream, 'This is TEXT file. Hello world, human!');

        $this->testMetadataInstance1 = new Metadata(__DIR__.'/sample1.jpg');
        $this->testMetadataInstance2 = new Metadata(__DIR__.'/sample2.jpg');
        $this->testMetadataInstance3 = new Metadata(__DIR__.'/sample3.png');
        $this->testMetadataInstance4 = new Metadata($stream);
        $this->testMetadataInstance5 = new Metadata(0);
    }

    public function test_getDatetime()
    {
        $this->assertInstanceOf(\DateTime::class, $this->testMetadataInstance1->getDatetime());
        $this->assertInstanceOf(\DateTime::class, $this->testMetadataInstance2->getDatetime());
        $this->assertNull($this->testMetadataInstance3->getDatetime());
        $this->assertNull($this->testMetadataInstance4->getDatetime());
        $this->assertNull($this->testMetadataInstance5->getDatetime());
    }

    public function test_getThumbnail()
    {
        $this->assertNotEmpty($this->testMetadataInstance1->getThumbnail());
        $this->assertNotEmpty($this->testMetadataInstance2->getThumbnail(true));
        $this->assertNotEmpty($this->testMetadataInstance3->getThumbnail());
        $this->assertEmpty($this->testMetadataInstance4->getThumbnail());
        $this->assertEmpty($this->testMetadataInstance5->getThumbnail(true));
    }

    public function test_getTags()
    {
        $this->assertEmpty($this->testMetadataInstance1->getTags());
        $this->assertNotEmpty($this->testMetadataInstance2->getTags());
        $this->assertEmpty($this->testMetadataInstance3->getTags());
        $this->assertEmpty($this->testMetadataInstance4->getTags());
        $this->assertEmpty($this->testMetadataInstance5->getTags());
    }

    public function test_getDescription()
    {
        $this->assertNotEmpty($this->testMetadataInstance1->getDescription());
        $this->assertNotEmpty($this->testMetadataInstance2->getDescription());
        $this->assertEmpty($this->testMetadataInstance3->getDescription());
        $this->assertEmpty($this->testMetadataInstance4->getDescription());
        $this->assertEmpty($this->testMetadataInstance5->getDescription());
    }

    public function test_getGeoJsonArray()
    {
        $this->assertNotEmpty($this->testMetadataInstance1->getGeoJsonArray());
        $this->assertEmpty($this->testMetadataInstance2->getGeoJsonArray());
        $this->assertEmpty($this->testMetadataInstance3->getGeoJsonArray());
        $this->assertEmpty($this->testMetadataInstance4->getGeoJsonArray());
        $this->assertEmpty($this->testMetadataInstance5->getGeoJsonArray());
    }

    public function test_toArray()
    {
        $this->assertNotEmpty($this->testMetadataInstance1->toArray());
        $this->assertNotEmpty($this->testMetadataInstance2->toArray());
        $this->assertEmpty($this->testMetadataInstance3->toArray());
        $this->assertEmpty($this->testMetadataInstance4->toArray());
        $this->assertEmpty($this->testMetadataInstance5->toArray());
    }

    public function test_toString()
    {
        $this->assertNotEquals(json_encode([]), (string)$this->testMetadataInstance1);
        $this->assertNotEquals(json_encode([]), (string)$this->testMetadataInstance2);
        $this->assertEquals(json_encode([]), (string)$this->testMetadataInstance3);
        $this->assertEquals(json_encode([]), (string)$this->testMetadataInstance4);
        $this->assertEquals(json_encode([]), (string)$this->testMetadataInstance5);
    }
}
