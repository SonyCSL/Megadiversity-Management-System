<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use artichoke\framework\controllers\FileController;
use artichoke\framework\models\entry\Entry;

require_once dirname(dirname(__DIR__)).'/common/GenRootDir.php';

class FileControllerTest extends MongodbTestCase
{
    const TESTFILEARRAY = [
        'album_id' => 1,
        'device_id' => 3,
        'contentType' => 'application/octet-stream',
        'meta' => ['hoge' => 'fuga'],
        'thumbnailB64' => 'aG90Y29mZmVl',
    ];
    const TESTIMAGEARRAY = [
        'album_id' => 2,
        'device_id' => 4,
        'contentType' => 'image/png',
        'thumbnailB64' => 'Y2FmZS1hdS1sYWl0IQ==',
    ];
    private $root;

    public function setUp()
    {
        Entry::setDatabase($this->getTestDatabase());

        $this->root = (new GenRootDir())->gen_root_dir();
    }
    /**
     * @doesNotPerformAssertions
     */
    public function initialize()
    {
        $_SERVER['SERVER_NAME'] = 'server';
        $_SERVER['SERVER_PROTOCOL'] = 'protocol';
    }
    /**
     * @doesNotPerformAssertions
     */
    public function createPngImage(): string
    {
        // create image by GD
        $test_img = imagecreatetruecolor(200, 150);
        $test_img_random_color = imagecolorallocate($test_img, rand(0, 255), rand(0, 255), rand(0, 255));
        imagefill($test_img, 0, 0, $test_img_random_color);
        // test png image
        ob_start();
        imagepng($test_img, null, 6);
        return ob_get_clean();
    }

    //test for indexAction
    /**
     * @runInSeparateProcess
     */
    public function test_indexAction_ArgsNull()
    {
        $this->initialize();

        $fileController = new FileController();

        $ref = new ReflectionClass(get_class($fileController));

        $fileController->indexAction();

        $refVal = $ref->getProperty('notFound');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($fileController);

        unset($fileController); # destruct
        $browser_output = $this->getActualOutput();

        $this->assertTrue($out);
        $this->assertEmpty($browser_output);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_indexAction_FileNotExists()
    {
        $this->initialize();

        $fileController = new FileController();

        $ref = new ReflectionClass(get_class($fileController));

        $fileController->indexAction(['this_is_dummy_fileid']);

        $refVal = $ref->getProperty('notFound');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($fileController);

        unset($fileController); # destruct
        $browser_output = $this->getActualOutput();

        $this->assertTrue($out);
        $this->assertEmpty($browser_output);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_indexAction_FileExistsOnTemporary()
    {
        $this->initialize();

        $fileController = new FileController();

        $ref = new ReflectionClass(get_class($fileController));

        $testBinary = $this->getRandomBinary(128);
        $testFilePath = $this->getTmpfilePathFromBinary($testBinary);
        $filepathArray = explode("/", $testFilePath);
        $testFileId = array_pop($filepathArray);

        $fileController->indexAction([$testFileId]);

        $refVal = $ref->getProperty('notFound');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($fileController);

        unset($fileController); # destruct
        $browser_output = $this->getActualOutput();

        $this->assertFalse($out);
        $this->assertEquals($testBinary, $browser_output);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_indexAction_FileExists()
    {
        $this->initialize();

        $fileController = new FileController();

        $ref = new ReflectionClass(get_class($fileController));

        $testBinary = $this->getRandomBinary(128);
        $testFilePath = $this->getTmpfilePathFromBinary($testBinary);
        $testFileId = $this->createTestFile($testFilePath, 'TEST_FILE_NAME', self::TESTFILEARRAY);

        $fileController->indexAction([$testFileId]);

        $refVal = $ref->getProperty('notFound');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($fileController);

        unset($fileController); # destruct
        $browser_output = $this->getActualOutput();

        $this->assertFalse($out);
        $this->assertEquals($testBinary, $browser_output);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_indexAction_CheckDenyRelativePass()
    {
        $this->initialize();

        $fileController = new FileController();

        $ref = new ReflectionClass(get_class($fileController));

        $testBinary = $this->getRandomBinary(128);
        $testFilePath = $this->getTmpfilePathFromBinary($testBinary);
        $testFileId = $this->createTestFile($testFilePath, 'TEST_FILE_NAME', self::TESTFILEARRAY);

        $tmp_last_path = basename(sys_get_temp_dir());
        $fileController->indexAction(['../'.$tmp_last_path.'/'.$testFileId]);

        $refVal = $ref->getProperty('notFound');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($fileController);

        unset($fileController); # destruct

        $this->assertTrue($out);
    }

    // test for imageAction
    /**
     * @runInSeparateProcess
     */
    public function test_imageAction_ArgsNull()
    {
        $this->initialize();

        $fileController = new FileController();

        $ref = new ReflectionClass(get_class($fileController));

        $fileController->imageAction();

        $refVal = $ref->getProperty('notFound');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($fileController);

        unset($fileController); # destruct
        $browser_output = $this->getActualOutput();

        $this->assertTrue($out);
        $this->assertEmpty($browser_output);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_imageAction_ArgsNotNullButNotExists()
    {
        $this->initialize();

        $fileController = new FileController();

        $ref = new ReflectionClass(get_class($fileController));

        $fileController->imageAction(['this_is_dummy_image_id']);

        $refVal = $ref->getProperty('notFound');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($fileController);

        unset($fileController); # destruct
        $browser_output = $this->getActualOutput();

        $this->assertTrue($out);
        $this->assertEmpty($browser_output);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_imageAction_isImageFile()
    {
        $this->initialize();

        $testBinary = $this->createPngImage();
        $testFilePath = $this->getTmpfilePathFromBinary($testBinary);
        $testFileId = $this->createTestFile($testFilePath, 'TEST_PNG_IMAGE_NAME', self::TESTIMAGEARRAY);

        $fileController1 = new FileController();
        $ref1 = new ReflectionClass(get_class($fileController1));
        $fileController2 = new FileController();
        $ref2 = new ReflectionClass(get_class($fileController2));

        // original size image
        $fileController1->imageAction([(string)$testFileId]);

        $notFound = $ref1->getProperty('notFound');
        $notFound->setAccessible(true);
        $out1 = $notFound->getValue($fileController1);
        $contentType = $ref1->getProperty('contentType');
        $contentType->setAccessible(true);
        $out2 = $contentType->getValue($fileController1);
        $binary_original = $ref1->getProperty('binary');
        $binary_original->setAccessible(true);
        $binary1 = $binary_original->getValue($fileController1);

        ob_start();
        unset($fileController1); # destruct
        $browser_output1 = ob_get_clean();

        $this->assertFalse($out1);
        $this->assertEquals(self::TESTIMAGEARRAY['contentType'], $out2);

        // with resizing requests
        $_REQUEST['w'] = 160;
        $_REQUEST['h'] = 120;
        $fileController2->imageAction([(string)$testFileId]);

        $binary_resized = $ref2->getProperty('binary');
        $binary_resized->setAccessible(true);
        $binary2 = $binary_resized->getValue($fileController2);

        ob_start();
        unset($fileController2); # destruct
        $browser_output2 = ob_get_clean();

        $this->assertNotEquals($binary1, $binary2);
        $this->assertNotEquals($browser_output1, $browser_output2);
        $this->assertEquals($binary1, $browser_output1);
        $this->assertEquals($binary2, $browser_output2);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_imageAction_isNotImageFile()
    {
        $this->initialize();

        $fileController = new FileController();

        $ref = new ReflectionClass(get_class($fileController));

        $testBinary = $this->getRandomBinary(128);
        $testFilePath = $this->getTmpfilePathFromBinary($testBinary);
        $testFileId = $this->createTestFile($testFilePath, 'TEST_FILE_NAME', self::TESTFILEARRAY);

        $fileController->imageAction([(string)$testFileId]);

        $notFound = $ref->getProperty('notFound');
        $notFound->setAccessible(true);
        $out1 = $notFound->getValue($fileController);
        $contentType = $ref->getProperty('contentType');
        $contentType->setAccessible(true);
        $out2 = $contentType->getValue($fileController);

        unset($fileController); # destruct
        $browser_output = $this->getActualOutput();

        $this->assertFalse($out1);
        $this->assertEquals('jpeg', $out2);
        $this->assertNotEmpty($browser_output);
    }

    // test for thumbnailAction
    /**
     * @runInSeparateProcess
     */
    public function test_thumbnailAction_ArgsNull()
    {
        $this->initialize();

        $fileController = new FileController();

        $ref = new ReflectionClass(get_class($fileController));

        $fileController->thumbnailAction();

        $refVal = $ref->getProperty('notFound');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($fileController);

        unset($fileController); # destruct
        $browser_output = $this->getActualOutput();

        $this->assertTrue($out);
        $this->assertEmpty($browser_output);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_thumbnailAction_FileNotExists()
    {
        $this->initialize();

        $fileController = new FileController();

        $ref = new ReflectionClass(get_class($fileController));

        $fileController->thumbnailAction(['test']);

        $refVal = $ref->getProperty('notFound');
        $refVal->setAccessible(true);
        $out = $refVal->getValue($fileController);

        unset($fileController); # destruct
        $browser_output = $this->getActualOutput();

        $this->assertTrue($out);
        $this->assertEmpty($browser_output);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_thumbnailAction_isImageFile()
    {
        $this->initialize();

        $fileController = new FileController();

        $ref = new ReflectionClass(get_class($fileController));

        $testBinary = $this->createPngImage();
        $testFilePath = $this->getTmpfilePathFromBinary($testBinary);
        $testFileId = $this->createTestFile($testFilePath, 'TEST_PNG_IMAGE_NAME', self::TESTIMAGEARRAY);

        $fileController->thumbnailAction([(string)$testFileId]);

        $notFound = $ref->getProperty('notFound');
        $notFound->setAccessible(true);
        $out1 = $notFound->getValue($fileController);
        $contentType = $ref->getProperty('contentType');
        $contentType->setAccessible(true);
        $out2 = $contentType->getValue($fileController);

        unset($fileController); # destruct
        $browser_output = $this->getActualOutput();

        $this->assertFalse($out1);
        $this->assertEquals(self::TESTIMAGEARRAY['contentType'], $out2);
        $this->assertEquals(base64_decode(self::TESTIMAGEARRAY['thumbnailB64']), $browser_output);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_thumbnailAction_isNotImageFile()
    {
        $this->initialize();

        $fileController = new FileController();

        $ref = new ReflectionClass(get_class($fileController));

        $testBinary = $this->getRandomBinary(128);
        $testFilePath = $this->getTmpfilePathFromBinary($testBinary);
        $testFileId = $this->createTestFile($testFilePath, 'TEST_FILE_NAME', self::TESTFILEARRAY);

        $fileController->thumbnailAction([(string)$testFileId]);

        $notFound = $ref->getProperty('notFound');
        $notFound->setAccessible(true);
        $out1 = $notFound->getValue($fileController);
        $contentType = $ref->getProperty('contentType');
        $contentType->setAccessible(true);
        $out2 = $contentType->getValue($fileController);

        unset($fileController); # destruct
        $browser_output = $this->getActualOutput();

        $this->assertFalse($out1);
        $this->assertEquals('jpeg', $out2);
        $this->assertEquals(base64_decode(self::TESTFILEARRAY['thumbnailB64']), $browser_output);
    }
}
