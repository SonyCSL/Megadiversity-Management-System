<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use artichoke\framework\abstracts\MongodbBase;
use artichoke\framework\models\entry\Entry;
use artichoke\framework\models\entry\Data;

class EntryTest extends MongodbTestCase
{
    private $already_existed_datastring_id;
    private $already_existed_readonly_datastring_id;
    private $testEntryInstance_create_mode;
    private $testEntryInstance_read_mode;
    private $testEntryInstance_read_mode_readonly;
    private $testEntryInstance_invalid_id;
    private $ref_testEntryInstance_create_mode;
    private $ref_testEntryInstance_read_mode;
    private $ref_testEntryInstance_read_mode_readonly;
    private $ref_testEntryInstance_invalid_id;
    private $ref_testPropertyDocument_create_mode;
    private $ref_testPropertyDocument_read_mode;
    private $ref_testPropertyDocument_read_mode_readonly;
    private $ref_testPropertyDocument_invalid_id;

    public function setUp()
    {
        MongodbBase::setDatabase($this->getTestDatabase()); # set mongodb test database

        // create some entry instances for tests
        $this->already_existed_datastring_id = $this->createTestDatastring(['album_id' => 1, 'device_id' => 3, 'hoga' => 'huge']);
        $this->already_existed_readonly_datastring_id = $this->createTestDatastring(['album_id' => 2, 'device_id' => 4, 'foo' => 'baz', 'lock' => true]);
        $this->testEntryInstance_create_mode = new Entry();
        $this->testEntryInstance_read_mode = new Entry($this->already_existed_datastring_id);
        $this->testEntryInstance_read_mode_readonly = new Entry($this->already_existed_readonly_datastring_id);
        $this->testEntryInstance_invalid_id = new Entry('waffle');

        // reflection classes for check getter and setter
        $this->ref_testEntryInstance_create_mode = new \ReflectionClass($this->testEntryInstance_create_mode);
        $this->ref_testEntryInstance_read_mode = new \ReflectionClass($this->testEntryInstance_read_mode);
        $this->ref_testEntryInstance_read_mode_readonly = new \ReflectionClass($this->testEntryInstance_read_mode_readonly);
        $this->ref_testEntryInstance_invalid_id = new \ReflectionClass($this->testEntryInstance_invalid_id);
        $this->ref_testPropertyDocument_create_mode = $this->ref_testEntryInstance_create_mode->getProperty('document');
        $this->ref_testPropertyDocument_create_mode->setAccessible(true);
        $this->ref_testPropertyDocument_read_mode = $this->ref_testEntryInstance_read_mode->getProperty('document');
        $this->ref_testPropertyDocument_read_mode->setAccessible(true);
        $this->ref_testPropertyDocument_read_mode_readonly = $this->ref_testEntryInstance_read_mode_readonly->getProperty('document');
        $this->ref_testPropertyDocument_read_mode_readonly->setAccessible(true);
        $this->ref_testPropertyDocument_invalid_id = $this->ref_testEntryInstance_invalid_id->getProperty('document');
        $this->ref_testPropertyDocument_invalid_id->setAccessible(true);
    }

    public function test_creation()
    {
        // create_mode pattern
        #1
        $create_result1 = $this->testEntryInstance_create_mode->create(); # create with no option
        $this->assertFalse($create_result1[0]);
        $this->assertEquals('Invalid target album id', $create_result1[1]);

        #2
        $this->testEntryInstance_create_mode->setAlbumId(18);
        $create_result2 = $this->testEntryInstance_create_mode->create(); # create with albumId
        $this->assertFalse($create_result2[0]);
        $this->assertEquals('Invalid associated device id', $create_result2[1]);

        #3
        $this->testEntryInstance_create_mode->setDeviceId(44);
        $create_result3 = $this->testEntryInstance_create_mode->create(); # create with albumId, deviceId
        $this->assertTrue($create_result3[0]);
        $this->assertInternalType('string', $create_result3[1]); # expected: new entry id
        $this->assertInstanceOf(\MongoDB\Model\BSONDocument::class, $create_result3[2]); # expected: BSONDocument
        $created_entry = $this->getTestBSONDocument(parent::COLLECTION_DATASTRING, new \MongoDB\BSON\ObjectId($create_result3[1]));
        $this->assertNotNull($created_entry); # existance check

        // read_mode pattern
        #4
        $create_result4 = $this->testEntryInstance_read_mode->create(); # create by read_mode
        $this->assertFalse($create_result4[0]);
        $this->assertEquals('This entry ID is already used or invalid', $create_result4[1]);

        #5
        $create_result5 = $this->testEntryInstance_read_mode_readonly->create(); # create by read_mode (readonly flag)
        $this->assertFalse($create_result5[0]);
        $this->assertEquals('This entry ID is already used or invalid', $create_result5[1]);

        // invald id pattern
        #6
        $create_result6 = $this->testEntryInstance_invalid_id->create(); # create by invalid_id
        $this->assertFalse($create_result6[0]);
        $this->assertEquals('This entry ID is already used or invalid', $create_result6[1]);
    }

    public function test_existance()
    {
        #1 : not exist (be creating)
        $this->assertFalse($this->testEntryInstance_create_mode->exists());
        #2 : already exist
        $this->assertTrue($this->testEntryInstance_read_mode->exists());
        #3 : already exist
        $this->assertTrue($this->testEntryInstance_read_mode_readonly->exists());
        #4 : not exist (invalid id)
        $this->assertFalse($this->testEntryInstance_invalid_id->exists());
    }

    public function test_deletion()
    {
        #1 : not exist (be creating)
        $this->assertFalse($this->testEntryInstance_create_mode->delete());
        #2 : already exist
        $this->assertTrue($this->testEntryInstance_read_mode->delete());
        $deleted_entry1 = $this->getTestBSONDocument(parent::COLLECTION_DATASTRING, $this->already_existed_datastring_id);
        $this->assertNull($deleted_entry1);
        #2+ : already deleted
        $this->assertFalse($this->testEntryInstance_read_mode->delete());
        #3 : already exist
        $this->assertTrue($this->testEntryInstance_read_mode_readonly->delete());
        $deleted_entry2 = $this->getTestBSONDocument(parent::COLLECTION_DATASTRING, $this->already_existed_readonly_datastring_id);
        $this->assertNull($deleted_entry2);
        #3+ : already deleted
        $this->assertFalse($this->testEntryInstance_read_mode_readonly->delete());
        #4 : not exist (invalid id)
        $this->assertFalse($this->testEntryInstance_invalid_id->delete());
    }

    public function test_setUploadMethod()
    {
        #1
        $this->testEntryInstance_create_mode->setUploadMethod('Cake-Bake-Take');
        $document1 = $this->ref_testPropertyDocument_create_mode->getValue($this->testEntryInstance_create_mode);
        $this->assertEquals('Cake-Bake-Take', $document1['uploadMethod']);
        #2
        $this->testEntryInstance_read_mode->setUploadMethod('556677');
        $document2 = $this->ref_testPropertyDocument_read_mode->getValue($this->testEntryInstance_read_mode);
        $this->assertEquals('556677', $document2['uploadMethod']);
        #3
        $this->testEntryInstance_read_mode_readonly->setUploadMethod('grapefruit');
        $document3 = $this->ref_testPropertyDocument_read_mode_readonly->getValue($this->testEntryInstance_read_mode_readonly);
        $this->assertArrayNotHasKey('uploadMethod', $document3);
        #4
        $this->testEntryInstance_invalid_id->setUploadMethod('cafe-latte');
        $document4 = $this->ref_testPropertyDocument_invalid_id->getValue($this->testEntryInstance_invalid_id);
        $this->assertEquals('UNKNOWN', $document4['uploadMethod']);
    }

    public function test_setDatetime()
    {
        #1
        $this->testEntryInstance_create_mode->setDatetime('2013-01-12 13:54:07');
        $document1 = $this->ref_testPropertyDocument_create_mode->getValue($this->testEntryInstance_create_mode);
        $this->assertInstanceOf(\MongoDB\BSON\UTCDateTime::class, $document1['userDate']);
        #2
        $this->testEntryInstance_read_mode->setDatetime(104532219);
        $document2 = $this->ref_testPropertyDocument_read_mode->getValue($this->testEntryInstance_read_mode);
        $this->assertInstanceOf(\MongoDB\BSON\UTCDateTime::class, $document2['userDate']);
        #3
        $this->testEntryInstance_read_mode_readonly->setDatetime(new \DateTime());
        $document3 = $this->ref_testPropertyDocument_read_mode_readonly->getValue($this->testEntryInstance_read_mode_readonly);
        $this->assertArrayNotHasKey('userDate', $document3);
        #4
        $this->testEntryInstance_invalid_id->setDatetime('2018-12-04 23:11:51');
        $document4 = $this->ref_testPropertyDocument_invalid_id->getValue($this->testEntryInstance_invalid_id);
        $this->assertArrayNotHasKey('userDate', $document4);
    }

    public function test_setaddTags()
    {
        #1
        $this->testEntryInstance_create_mode->setTags(['dim', 'dom', 'dam']);
        $document1 = $this->ref_testPropertyDocument_create_mode->getValue($this->testEntryInstance_create_mode);
        $this->assertEquals(['dim', 'dom', 'dam'], $document1['tags']);
        #2
        $this->testEntryInstance_create_mode->addTags('pim,pom,pam');
        $document2 = $this->ref_testPropertyDocument_create_mode->getValue($this->testEntryInstance_create_mode);
        $this->assertContains('pim', $document2['tags']);
        $this->assertContains('pom', $document2['tags']);
        $this->assertContains('pam', $document2['tags']);
        #3
        $this->testEntryInstance_read_mode->setTags('alps,baby,cookie');
        $document3 = $this->ref_testPropertyDocument_read_mode->getValue($this->testEntryInstance_read_mode);
        $this->assertEquals(['alps', 'baby', 'cookie'], $document3['tags']);
        #4
        $this->testEntryInstance_read_mode->addTags(['dot', 'error', 'family']);
        $document4 = $this->ref_testPropertyDocument_read_mode->getValue($this->testEntryInstance_read_mode);
        $this->assertContains('dot', $document4['tags']);
        $this->assertContains('error', $document4['tags']);
        $this->assertContains('family', $document4['tags']);
        #5
        $this->testEntryInstance_read_mode_readonly->setTags(['onigiri']);
        $document5 = $this->ref_testPropertyDocument_read_mode_readonly->getValue($this->testEntryInstance_read_mode_readonly);
        $this->assertArrayNotHasKey('tags', $document5);
        #6
        $this->testEntryInstance_invalid_id->settTags('egg,chicken');
        $document6 = $this->ref_testPropertyDocument_invalid_id->getValue($this->testEntryInstance_invalid_id);
        $this->assertArrayNotHasKey('tags', $document6);
    }

    public function test_setTimezone()
    {
        #1
        $this->testEntryInstance_create_mode->setTimezone(8.5);
        $document1 = $this->ref_testPropertyDocument_create_mode->getValue($this->testEntryInstance_create_mode);
        $this->assertEquals(8.5, $document1['timezone']);
        #2
        $this->testEntryInstance_read_mode->setTimezone(-6);
        $document2 = $this->ref_testPropertyDocument_read_mode->getValue($this->testEntryInstance_read_mode);
        $this->assertEquals(-6, $document2['timezone']);
        #3
        $this->testEntryInstance_read_mode_readonly->setTimezone(1);
        $document3 = $this->ref_testPropertyDocument_read_mode_readonly->getValue($this->testEntryInstance_read_mode_readonly);
        $this->assertArrayNotHasKey('timezone', $document3);
        #4
        $this->testEntryInstance_invalid_id->setTimezone(-12);
        $document4 = $this->ref_testPropertyDocument_invalid_id->getValue($this->testEntryInstance_invalid_id);
        $this->assertArrayNotHasKey('timezone', $document4);
    }

    public function test_setComment()
    {
        #1
        $this->testEntryInstance_create_mode->setComment('gag');
        $document1 = $this->ref_testPropertyDocument_create_mode->getValue($this->testEntryInstance_create_mode);
        $this->assertEquals('gag', $document1['comment']);
        #2
        $this->testEntryInstance_read_mode->setComment('1740527');
        $document2 = $this->ref_testPropertyDocument_read_mode->getValue($this->testEntryInstance_read_mode);
        $this->assertEquals('1740527', $document2['comment']);
        #3
        $this->testEntryInstance_read_mode_readonly->setComment('cat_dog');
        $document3 = $this->ref_testPropertyDocument_read_mode_readonly->getValue($this->testEntryInstance_read_mode_readonly);
        $this->assertArrayNotHasKey('comment', $document3);
        #4
        $this->testEntryInstance_invalid_id->setComment('null');
        $document4 = $this->ref_testPropertyDocument_invalid_id->getValue($this->testEntryInstance_invalid_id);
        $this->assertArrayNotHasKey('comment', $document4);
    }

    public function test_setThumbnail()
    {
        #1
        $this->testEntryInstance_create_mode->setThumbnail(base64_encode('drop'));
        $document1 = $this->ref_testPropertyDocument_create_mode->getValue($this->testEntryInstance_create_mode);
        $this->assertEquals(base64_encode('drop'), $document1['thumbnailB64']);
        #2
        $this->testEntryInstance_read_mode->setThumbnail(base64_encode('1234567890'));
        $document2 = $this->ref_testPropertyDocument_read_mode->getValue($this->testEntryInstance_read_mode);
        $this->assertEquals(base64_encode('1234567890'), $document2['thumbnailB64']);
        #3
        $this->testEntryInstance_read_mode_readonly->setThumbnail(base64_encode('bread'));
        $document3 = $this->ref_testPropertyDocument_read_mode_readonly->getValue($this->testEntryInstance_read_mode_readonly);
        $this->assertArrayNotHasKey('thumbnailB64', $document3);
        #4
        $this->testEntryInstance_invalid_id->setThumbnail(base64_encode('NaN'));
        $document4 = $this->ref_testPropertyDocument_invalid_id->getValue($this->testEntryInstance_invalid_id);
        $this->assertArrayNotHasKey('thumbnailB64', $document4);
    }

    public function test_setMetadata()
    {
        #1
        $this->testEntryInstance_create_mode->setMetadata(['drum' => 'perl']);
        $document1 = $this->ref_testPropertyDocument_create_mode->getValue($this->testEntryInstance_create_mode);
        $this->assertEquals(['drum' => 'perl'], $document1['meta']);
        #2
        $this->testEntryInstance_read_mode->setMetadata(['guitar' => 'fender']);
        $document2 = $this->ref_testPropertyDocument_read_mode->getValue($this->testEntryInstance_read_mode);
        $this->assertEquals(['guitar' => 'fender'], $document2['meta']);
        #3
        $this->testEntryInstance_read_mode_readonly->setMetadata(['bass' => 'teisco']);
        $document3 = $this->ref_testPropertyDocument_read_mode_readonly->getValue($this->testEntryInstance_read_mode_readonly);
        $this->assertArrayNotHasKey('meta', $document3);
        #4
        $this->testEntryInstance_invalid_id->setMetadata(['keyboard' => 'yamaha']);
        $document4 = $this->ref_testPropertyDocument_invalid_id->getValue($this->testEntryInstance_invalid_id);
        $this->assertArrayNotHasKey('meta', $document4);
    }

    public function test_setGeoJsonArray()
    {
        #1
        $this->testEntryInstance_create_mode->setGeoJsonArray(['drum' => 'tama']);
        $document1 = $this->ref_testPropertyDocument_create_mode->getValue($this->testEntryInstance_create_mode);
        $this->assertArraySubset(['drum' => 'tama'], $document1['geo']);
        $this->assertArrayHasKey('type', $document1['geo']);
        $this->assertArrayHasKey('coordinates', $document1['geo']);
        $this->assertArrayHasKey('place', $document1['geo']);
        $this->assertArrayHasKey('local_position', $document1['geo']);
        $this->assertArrayHasKey('altitude', $document1['geo']);
        $this->assertArrayHasKey('groundHeight', $document1['geo']);
        #2
        $this->testEntryInstance_read_mode->setGeoJsonArray(['guitar' => 'gibson']);
        $document2 = $this->ref_testPropertyDocument_read_mode->getValue($this->testEntryInstance_read_mode);
        $this->assertArraySubset(['guitar' => 'gibson'], $document2['geo']);
        $this->assertArrayHasKey('type', $document2['geo']);
        $this->assertArrayHasKey('coordinates', $document2['geo']);
        $this->assertArrayHasKey('place', $document2['geo']);
        $this->assertArrayHasKey('local_position', $document2['geo']);
        $this->assertArrayHasKey('altitude', $document2['geo']);
        $this->assertArrayHasKey('groundHeight', $document2['geo']);
        #3
        $this->testEntryInstance_read_mode_readonly->setGeoJsonArray(['bass' => 'ibanez']);
        $document3 = $this->ref_testPropertyDocument_read_mode_readonly->getValue($this->testEntryInstance_read_mode_readonly);
        $this->assertArrayNotHasKey('geo', $document3);
        #4
        $this->testEntryInstance_invalid_id->setGeoJsonArray(['keyboard' => 'roland']);
        $document4 = $this->ref_testPropertyDocument_invalid_id->getValue($this->testEntryInstance_invalid_id);
        $this->assertArrayNotHasKey('geo', $document4);
    }

    public function test_setData()
    {
        // mock
        $mock_data = $this->createMock(Data::class);
        $mock_data->method('toArray')->willReturn(['test' => 'ok']);
        #1
        $this->testEntryInstance_create_mode->setData($mock_data);
        $document1 = $this->ref_testPropertyDocument_create_mode->getValue($this->testEntryInstance_create_mode);
        $this->assertEquals(['test' => 'ok'], $document1['data']);
        #2
        $this->testEntryInstance_read_mode->setData($mock_data);
        $document2 = $this->ref_testPropertyDocument_read_mode->getValue($this->testEntryInstance_read_mode);
        $this->assertEquals(['test' => 'ok'], $document2['data']);
        #3
        $this->testEntryInstance_read_mode_readonly->setData($mock_data);
        $document3 = $this->ref_testPropertyDocument_read_mode_readonly->getValue($this->testEntryInstance_read_mode_readonly);
        $this->assertArrayNotHasKey('data', $document3);
        #4
        $this->testEntryInstance_invalid_id->setData($mock_data);
        $document4 = $this->ref_testPropertyDocument_invalid_id->getValue($this->testEntryInstance_invalid_id);
        $this->assertArrayNotHasKey('data', $document4);
    }

    public function test_getData()
    {
        #1
        $this->ref_testPropertyDocument_create_mode->setValue($this->testEntryInstance_create_mode, ['data' => ['temp' => 33.3]]);
        $this->assertEmpty((string)$this->testEntryInstance_create_mode->getData());
        #2
        $this->ref_testPropertyDocument_read_mode->setValue($this->testEntryInstance_read_mode, ['data' => ['temp' => 18.5]]);
        $this->assertNotEmpty((string)$this->testEntryInstance_read_mode->getData());
        #3
        $this->ref_testPropertyDocument_read_mode_readonly->setValue($this->testEntryInstance_read_mode_readonly, ['data' => ['temp' => 11.2]]);
        $this->assertNotEmpty((string)$this->testEntryInstance_read_mode_readonly->getData());
        #4
        $this->ref_testPropertyDocument_invalid_id->setValue($this->testEntryInstance_invalid_id, ['data' => ['temp' => 10.5]]);
        $this->assertEmpty((string)$this->testEntryInstance_invalid_id->getData());
    }

    public function test_getId()
    {
        #1
        $this->assertEmpty($this->testEntryInstance_create_mode->getId());
        #2
        $this->assertEquals((string)$this->already_existed_datastring_id, $this->testEntryInstance_read_mode->getId());
        #3
        $this->assertEquals((string)$this->already_existed_readonly_datastring_id, $this->testEntryInstance_read_mode_readonly->getId());
        #4
        $this->assertEmpty($this->testEntryInstance_invalid_id->getId());
    }

    public function test_getAlbumId()
    {
        #1
        $this->assertEmpty($this->testEntryInstance_create_mode->getAlbumId());
        #2
        $this->assertEquals(1, $this->testEntryInstance_read_mode->getAlbumId());
        #3
        $this->assertEquals(2, $this->testEntryInstance_read_mode_readonly->getAlbumId());
        #4
        $this->assertEmpty($this->testEntryInstance_invalid_id->getAlbumId());
    }

    public function test_getDeviceId()
    {
        #1
        $this->assertEmpty($this->testEntryInstance_create_mode->getDeviceId());
        #2
        $this->assertEquals(3, $this->testEntryInstance_read_mode->getDeviceId());
        #3
        $this->assertEquals(4, $this->testEntryInstance_read_mode_readonly->getDeviceId());
        #4
        $this->assertEmpty($this->testEntryInstance_invalid_id->getDeviceId());
    }

    public function test_getUserDateTime()
    {
        #1
        $this->ref_testPropertyDocument_create_mode->setValue($this->testEntryInstance_create_mode, ['userDate' => new \MongoDB\BSON\UTCDateTime()]);
        $this->assertNull($this->testEntryInstance_create_mode->getUserDateTime());
        #2
        $this->ref_testPropertyDocument_read_mode->setValue($this->testEntryInstance_read_mode, ['userDate' => new \MongoDB\BSON\UTCDateTime()]);
        $this->assertInstanceOf(\DateTime::class, $this->testEntryInstance_read_mode->getUserDateTime());
        #3
        // no insert
        $this->assertNull($this->testEntryInstance_read_mode_readonly->getUserDateTime());
        #4
        $this->assertNull($this->testEntryInstance_invalid_id->getUserDateTime());
    }

    public function test_getUploadDateTime()
    {
        $dt1 = new \DateTime();
        $dt2 = new \DateTime('12 hours ago');
        #1
        $this->ref_testPropertyDocument_read_mode->setValue($this->testEntryInstance_read_mode, ['uploadDate' => new \MongoDB\BSON\UTCDateTime($dt1)]);
        $this->assertEquals($dt1->format(\DateTime::ATOM), $this->testEntryInstance_read_mode->getUploadDateTime()->format(\DateTime::ATOM));
        #2
        $this->ref_testPropertyDocument_read_mode_readonly->setValue($this->testEntryInstance_read_mode_readonly, ['uploadDate' => new \MongoDB\BSON\UTCDateTime($dt2)]);
        $this->assertEquals($dt2->format(\DateTime::COOKIE), $this->testEntryInstance_read_mode_readonly->getUploadDateTime()->format(\DateTime::COOKIE));
    }

    public function test_getUploadMethod()
    {
        #1
        $this->ref_testPropertyDocument_create_mode->setValue($this->testEntryInstance_create_mode, ['uploadMethod' => 'HTTP_RAMEN']);
        $this->assertEmpty($this->testEntryInstance_create_mode->getUploadMethod());
        #2
        $this->ref_testPropertyDocument_read_mode->setValue($this->testEntryInstance_read_mode, ['uploadMethod' => 'HTTP_SOBA']);
        $this->assertEquals('HTTP_SOBA', $this->testEntryInstance_read_mode->getUploadMethod());
        #3
        $this->ref_testPropertyDocument_read_mode_readonly->setValue($this->testEntryInstance_read_mode_readonly, ['uploadMethod' => 'HTTP_UDON']);
        $this->assertEquals('HTTP_UDON', $this->testEntryInstance_read_mode_readonly->getUploadMethod());
        #4
        $this->ref_testPropertyDocument_invalid_id->setValue($this->testEntryInstance_invalid_id, ['uploadMethod' => 'HTTP_PASTA']);
        $this->assertEmpty($this->testEntryInstance_invalid_id->getUploadMethod());
    }

    public function test_getTags()
    {
        #1
        $this->ref_testPropertyDocument_create_mode->setValue($this->testEntryInstance_create_mode, ['tags' => ['red', 'green', 'blue']]);
        $this->assertEmpty($this->testEntryInstance_create_mode->getTags());
        #2
        $this->ref_testPropertyDocument_read_mode->setValue($this->testEntryInstance_read_mode, ['tags' => ['magenta', 'cyan', 'yellow']]);
        $this->assertEquals(['magenta', 'cyan', 'yellow'], $this->testEntryInstance_read_mode->getTags('black'));
        #3
        $this->assertEmpty($this->testEntryInstance_read_mode_readonly->getTags());
        #4
        $this->assertEquals(['INVALID!'], $this->testEntryInstance_invalid_id->getTags('INVALID!'));
    }

    public function test_getComment()
    {
        $hamlet = 'Hamlet is a tragedy and revenge play by William Shakespeare. It is one of his best-known works, one of the most-quoted writings in the English language and is universally included on lists of the world’s greatest books.';
        $sonnets = 'Shakespeare\'s sonnets is a collection of 154 poems in sonnet form written by William Shakespeare that deal with such themes as love, beauty, politics, and mortality.';
        #1
        $this->ref_testPropertyDocument_create_mode->setValue($this->testEntryInstance_create_mode, ['comment' => $hamlet]);
        $this->assertEmpty($this->testEntryInstance_create_mode->getComment());
        #2
        $this->ref_testPropertyDocument_read_mode->setValue($this->testEntryInstance_read_mode, ['comment' => $sonnets]);
        $this->assertEquals($sonnets, $this->testEntryInstance_read_mode->getComment('Shakespeare?'));
        #3
        $this->assertEmpty($this->testEntryInstance_read_mode_readonly->getComment());
        #4
        $this->assertEquals('William!', $this->testEntryInstance_invalid_id->getComment('William!'));
    }

    public function test_getTimezone()
    {
        #1
        $this->ref_testPropertyDocument_create_mode->setValue($this->testEntryInstance_create_mode, ['timezone' => -5.0]);
        $this->assertEmpty($this->testEntryInstance_create_mode->getTimezone());
        #2
        $this->ref_testPropertyDocument_read_mode->setValue($this->testEntryInstance_read_mode, ['timezone' => 9.5]);
        $this->assertEquals(9.5, $this->testEntryInstance_read_mode->getTimezone());
        #3
        $this->assertEmpty($this->testEntryInstance_read_mode_readonly->getTimezone());
        #4
        $this->assertEmpty($this->testEntryInstance_invalid_id->getTimezone());
    }

    public function test_addDocumentAtString()
    {
        $lemon = 'Lemon';
        $orange = 'Orange';
        $this->testEntryInstance_read_mode->setComment($lemon);
        $this->testEntryInstance_read_mode->addDocument('comment', $orange);
        $document = $this->ref_testPropertyDocument_read_mode->getValue($this->testEntryInstance_read_mode);
        $this->assertEquals($lemon.$orange, $document['comment']);
    }

    public function test_getAllDocument()
    {
        #1
        $this->assertNull($this->testEntryInstance_create_mode->getDocument());
        #2
        $this->assertArraySubset(['album_id' => 1, 'device_id' => 3, 'hoga' => 'huge'], $this->testEntryInstance_read_mode->getDocument(''));
        #3
        $this->assertArraySubset(['album_id' => 2, 'device_id' => 4, 'foo' => 'baz', 'lock' => true], $this->testEntryInstance_read_mode_readonly->getDocument());
        #4
        $this->assertNull($this->testEntryInstance_invalid_id->getDocument());
    }

    public function test_setFile()
    {
        #1
        $this->testEntryInstance_create_mode->setFile('/var/test');
        $filepath1 = $this->ref_testEntryInstance_create_mode->getProperty('filepath');
        $filepath1->setAccessible(true);
        $this->assertEquals('/var/test', $filepath1->getValue($this->testEntryInstance_create_mode));
        $filename1 = $this->ref_testEntryInstance_create_mode->getProperty('filename');
        $filename1->setAccessible(true);
        $this->assertEquals('untitled', $filename1->getValue($this->testEntryInstance_create_mode));
        #2
        $this->testEntryInstance_read_mode->setFile('/home/me/tttt', 'Chicken_Nan-ban');
        $filepath2 = $this->ref_testEntryInstance_read_mode->getProperty('filepath');
        $filepath2->setAccessible(true);
        $this->assertEquals('/home/me/tttt', $filepath2->getValue($this->testEntryInstance_read_mode));
        $filename2 = $this->ref_testEntryInstance_read_mode->getProperty('filename');
        $filename2->setAccessible(true);
        $this->assertEquals('Chicken_Nan-ban', $filename2->getValue($this->testEntryInstance_read_mode));
    }

    public function test_magic_methods()
    {
        // _call()
        $this->assertEquals('Not supported method is called', $this->testEntryInstance_create_mode->get('hey'));
        $this->assertEquals('Not supported method is called', $this->testEntryInstance_read_mode->set(1));
        // _toString()
        $this->assertEmpty((string)$this->testEntryInstance_create_mode);
        $this->assertEquals((string)$this->already_existed_datastring_id, (string)$this->testEntryInstance_read_mode);
    }
}
