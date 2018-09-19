<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\models\entry\Data;

class DataTest extends TestCase
{
    const TESTARRAY1 = [
        'temparature' => [
            'value' => 18.9,
            'unit' => '℃',
        ],
        'humidity' => [
            'value' => 66.1,
        ],
        'brightness' => 997,
    ];
    const TESTARRAY2 = [
        'temparature' => 11,
        'trap_on' => [
            'value' => false,
        ],
        'sensor_config' => [
            'resolution' => 1024,
        ],
    ];
    const JSON_VALID = '{"forecast":{"value":"sunny"}}';
    const JSON_INVALID = '{pressure:{value:1.098341}}';
    private $testDataInstance_array;
    private $testDataInstance_arrayobj;
    private $testDataInstance_json_valid;
    private $testDataInstance_json_invalid;
    private $testDataInstance_construct_invalid;

    public function setUp()
    {
        $this->testDataInstance_array = new Data(self::TESTARRAY1);
        $this->testDataInstance_arrayobj = new Data(new \ArrayObject(self::TESTARRAY2));
        $this->testDataInstance_json_valid = new Data(self::JSON_VALID);
        $this->testDataInstance_json_invalid = new Data(self::JSON_INVALID);
        $this->testDataInstance_construct_invalid = new Data(null);
    }

    public function test_getSomeTypesOfValues()
    {
        $this->assertEquals(11, $this->testDataInstance_arrayobj->getInt('temparature'));
        $this->assertEquals(18.9, $this->testDataInstance_array->getFloat('temparature'));
        $this->assertFalse($this->testDataInstance_arrayobj->getBool('trap_on'));
        $this->assertEquals('sunny', $this->testDataInstance_json_valid->getString('forecast'));

        // with_unit
        $this->assertEquals('18.9℃', $this->testDataInstance_array->get('temparature', true));
        $this->assertEquals('66.1', $this->testDataInstance_array->get('humidity', true));

        // null
        $this->assertNull($this->testDataInstance_array->get('soil_moisture'));
        $this->assertNull($this->testDataInstance_arrayobj->get('sensor_config'));
        $this->assertNull($this->testDataInstance_json_invalid->get('pressure'));
        $this->assertNull($this->testDataInstance_construct_invalid->get('water_level'));
    }

    public function test_getParseError()
    {
        $this->assertEmpty($this->testDataInstance_json_valid->getParseError());
        $this->assertNotEmpty($this->testDataInstance_json_invalid->getParseError());
    }

    public function test_toJson()
    {
        $this->assertEquals(json_encode(self::TESTARRAY1, JSON_UNESCAPED_UNICODE), $this->testDataInstance_array->toJson());
        $this->assertEquals(json_encode(self::TESTARRAY2, JSON_UNESCAPED_UNICODE), $this->testDataInstance_arrayobj->toJson());
        $this->assertEquals(self::JSON_VALID, $this->testDataInstance_json_valid->toJson());
        $this->assertEquals(json_encode([]), $this->testDataInstance_json_invalid->toJson());
    }

    public function test_toArray()
    {
        $this->assertEquals(self::TESTARRAY1, $this->testDataInstance_array->toArray());
        $this->assertEquals(self::TESTARRAY2, $this->testDataInstance_arrayobj->toArray());
        $this->assertEquals(json_decode(self::JSON_VALID, true), $this->testDataInstance_json_valid->toArray());
        $this->assertEquals([], $this->testDataInstance_json_invalid->toArray());
    }

    public function test_set()
    {
        $ref = new \ReflectionClass($this->testDataInstance_array);
        $dataProperty = $ref->getProperty('data');
        $dataProperty->setAccessible(true);

        $this->testDataInstance_array->set('foo', 'bar');
        $this->testDataInstance_array->set('akb', 48, 'people');
        $data = $dataProperty->getValue($this->testDataInstance_array);
        $this->assertArraySubset([
            'foo' => [
                'value' => 'bar',
                'unit' => '',
            ],
            'akb' => [
                'value' => 48,
                'unit' => 'people',
            ],
        ], $data);
    }
}
