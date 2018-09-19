<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use artichoke\framework\abstracts\MongodbBase;
use artichoke\framework\models\entry\Image;

class ImageTest extends MongodbTestCase
{
    const BIN_BYTES = 1024;
    const TESTARRAY_BIN = [
        'album_id' => 1,
        'device_id' => 4,
        'contentType' => 'application/octet-stream',
        'meta' => ['hoge' => 'fuga'],
        'thumbnailB64' => 'QUJDREVGRw==',
    ];
    const TESTARRAY_JPG = [
        'album_id' => 2,
        'device_id' => 5,
        'contentType' => 'image/jpeg',
        'meta' => ['foo' => 'baz'],
    ];
    const TESTARRAY_PNG = [
        'album_id' => 3,
        'device_id' => 6,
        'contentType' => 'image/png',
        'lock' => true,
    ];
    private $testBinary_bin;
    private $testBinary_jpg;
    private $testBinary_png;
    private $testBinary_portrait;
    private $testBinaryPath_bin;
    private $testBinaryPath_jpg;
    private $testBinaryPath_png;
    private $testBinaryPath_portrait;
    private $testImageObjectId_bin;
    private $testImageObjectId_jpg;
    private $testImageObjectId_png;
    private $testImageObjectId_portrait;
    private $testImageInstance_bin;
    private $testImageInstance_jpg;
    private $testImageInstance_png;
    private $testImageInstance_portrait;

    public function __construct()
    {
        parent::__construct();

        // create image by GD
        $test_img = imagecreatetruecolor(200, 150);
        $test_img_random_color = imagecolorallocate($test_img, rand(0, 255), rand(0, 255), rand(0, 255));
        imagefill($test_img, 0, 0, $test_img_random_color);

        // Y-long image
        $test_img_portrait = imagecreatetruecolor(100, 300);
        $test_img_portrait_random_color = imagecolorallocate($test_img_portrait, rand(0, 255), rand(0, 255), rand(0, 255));
        imagefill($test_img_portrait, 0, 0, $test_img_portrait_random_color);

        // test jpg image
        ob_start();
        imagejpeg($test_img, null, 75);
        $this->testBinary_jpg = ob_get_clean();
        // test png image
        ob_start();
        imagepng($test_img, null, 6);
        $this->testBinary_png = ob_get_clean();
        // test portrait image (jpg)
        ob_start();
        imagejpeg($test_img_portrait, null, 75);
        $this->testBinary_portrait = ob_get_clean();
        //test binary (not image)
        $this->testBinary_bin = $this->getRandomBinary(self::BIN_BYTES);
    }

    public function setUp()
    {
        MongodbBase::setDatabase($this->getTestDatabase()); # set mongodb test database

        $this->testBinaryPath_bin = $this->getTmpfilePathFromBinary($this->testBinary_bin);
        $this->testBinaryPath_jpg = $this->getTmpfilePathFromBinary($this->testBinary_jpg);
        $this->testBinaryPath_png = $this->getTmpfilePathFromBinary($this->testBinary_png);
        $this->testBinaryPath_portrait = $this->getTmpfilePathFromBinary($this->testBinary_portrait);

        $this->testImageObjectId_bin = $this->createTestFile($this->testBinaryPath_bin, 'Test Image (bin)', self::TESTARRAY_BIN);
        $this->testImageObjectId_jpg = $this->createTestFile($this->testBinaryPath_jpg, 'Test Image (jpg)', self::TESTARRAY_JPG);
        $this->testImageObjectId_png = $this->createTestFile($this->testBinaryPath_png, 'Test Image (png)', self::TESTARRAY_PNG);
        $this->testImageObjectId_portrait = $this->createTestFile($this->testBinaryPath_portrait, 'Test Image (portrait)', self::TESTARRAY_JPG);

        $this->testImageInstance_bin = new Image($this->testImageObjectId_bin);
        $this->testImageInstance_jpg = new Image($this->testImageObjectId_jpg);
        $this->testImageInstance_png = new Image($this->testImageObjectId_png);
        $this->testImageInstance_portrait = new Image($this->testImageObjectId_portrait);
    }

    public function test_isImage()
    {
        $this->assertFalse($this->testImageInstance_bin->isImage());
        $this->assertTrue($this->testImageInstance_jpg->isImage());
        $this->assertTrue($this->testImageInstance_png->isImage());
        $this->assertTrue($this->testImageInstance_portrait->isImage());
    }

    public function test_getBinary()
    {
        $this->assertEquals($this->testImageInstance_bin->getAlternativeImageBinary(), $this->testImageInstance_bin->getBinary());
        $this->assertEquals($this->testBinary_jpg, $this->testImageInstance_jpg->getBinary());
        $this->assertEquals($this->testBinary_png, $this->testImageInstance_png->getBinary());
        $this->assertEquals($this->testBinary_portrait, $this->testImageInstance_portrait->getBinary());
    }

    public function test_getBinaryStream()
    {
        $this->assertEquals($this->testImageInstance_bin->getAlternativeImageBinary(), stream_get_contents($this->testImageInstance_bin->getBinaryStream(), -1, 0));
        $this->assertEquals($this->testBinary_jpg, stream_get_contents($this->testImageInstance_jpg->getBinaryStream()));
        $this->assertEquals($this->testBinary_png, stream_get_contents($this->testImageInstance_png->getBinaryStream()));
        $this->assertEquals($this->testBinary_portrait, stream_get_contents($this->testImageInstance_portrait->getBinaryStream()));
    }

    public function test_resize()
    {
        // thumbnail size (default: 140*105)
        $this->assertNotEquals($this->testImageInstance_bin->getAlternativeImageBinary(), $this->testImageInstance_bin->resize()->getBinary());
        $this->assertNotEquals($this->testBinary_jpg, $this->testImageInstance_jpg->resize()->getBinary());
        $this->assertNotEquals($this->testBinary_png, $this->testImageInstance_png->resize()->getBinary());
        $this->assertNotEquals($this->testBinary_portrait, $this->testImageInstance_portrait->resize()->getBinary());
        // white background
        $this->assertNotEquals($this->testImageInstance_bin->getAlternativeImageBinary(), $this->testImageInstance_bin->resize(null, null, 'FFFFFF')->getBinary());
        $this->assertNotEquals($this->testBinary_jpg, $this->testImageInstance_jpg->resize(null, null, 'FFFFFF')->getBinary());
        $this->assertNotEquals($this->testBinary_png, $this->testImageInstance_png->resize(null, null, 'FFFFFF')->getBinary());
        $this->assertNotEquals($this->testBinary_portrait, $this->testImageInstance_portrait->resize(null, null, 'FFFFFF')->getBinary());
    }

    public function test_addWatermark()
    {
        $this->assertNotEquals($this->testImageInstance_bin->getAlternativeImageBinary(), $this->testImageInstance_bin->addWatermark('binbin')->getBinary());
        $this->assertNotEquals($this->testBinary_jpg, $this->testImageInstance_jpg->addWatermark('jeypeg')->getBinary());
        $this->assertNotEquals($this->testBinary_png, $this->testImageInstance_png->addWatermark('ping')->getBinary());
        $this->assertNotEquals($this->testBinary_portrait, $this->testImageInstance_portrait->addWatermark('tatenaga')->getBinary());
    }

    public function test_convertTo()
    {
        // bin
        $this->assertNotEquals($this->testImageInstance_bin->getAlternativeImageBinary(), $this->testImageInstance_bin->convertTo('gif')->getBinary());
        $this->assertNotEquals($this->testImageInstance_bin->getAlternativeImageBinary(), $this->testImageInstance_bin->convertTo('png')->getBinary());
        $this->assertNotEquals($this->testImageInstance_bin->getAlternativeImageBinary(), $this->testImageInstance_bin->convertTo('bmp')->getBinary());
        // alternative
        $jpgLength = strlen($this->testBinary_jpg);
        $pngLength = strlen($this->testBinary_png);
        $convertedJpgLength = strlen($this->testImageInstance_png->convertTo('jpg')->getBinary());
        $convertedPngLength = strlen($this->testImageInstance_jpg->convertTo('png')->getBinary());
        $this->assertTrue(($jpgLength - 1 <= $convertedJpgLength || $convertedJpgLength <= $jpgLength + 1)); # png -> jpg
        $this->assertTrue(($pngLength - 1 <= $convertedPngLength || $convertedPngLength <= $pngLength + 1)); # jpg -> png
        // fail (same output)
        $this->assertEquals($this->testBinary_portrait, $this->testImageInstance_portrait->convertTo('unknown_type')->getBinary());
    }
}
