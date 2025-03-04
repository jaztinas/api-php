<?php

namespace Bookboon\Api\Entity;

use Bookboon\Api\Bookboon;
use Bookboon\Api\Client\BasicAuthClient;
use Bookboon\Api\Client\Headers;
use PHPUnit\Framework\TestCase;

/**
 * Class BookTest
 * @package Bookboon\Api\Entity
 * @group entity
 * @group book
 */
class BookTest extends TestCase
{
    /** @var Book */
    private static $data = null;

    /** @var Bookboon */
    private static $bookboon = null;

    public static function setUpBeforeClass()
    {
        include_once(__DIR__ . '/../Helpers.php');
        self::$bookboon = \Helpers::getBookboon();
        self::$data = Book::get(self::$bookboon, '3bf58559-034f-4676-bb5f-a2c101015a58', true)
            ->getEntityStore()
            ->getSingle();
    }

    public function testGetId()
    {
        $this->assertEquals('3bf58559-034f-4676-bb5f-a2c101015a58', self::$data->getId());
    }


    public function providerTestGetters()
    {
        return [
            'getTitle' => ['getTitle'],
            'getHomepage' => ['getHomepage'],
            'getAuthors' => ['getAuthors'],
            'getIsbn' => ['getIsbn'],
            'getLanguageName' => ['getLanguageName'],
            'getLanguageCode' => ['getLanguageCode'],
            'getPublished' => ['getPublished'],
            'getAbstract' => ['getAbstract'],
            'getEdition' => ['getEdition'],
            'getPages' => ['getPages'],
            'getPriceLevel' => ['getPriceLevel'],
            'getRatingCount' => ['getRatingCount'],
            'getRatingAverage' => ['getRatingAverage'],
            'getFormats' => ['getFormats'],
            'getVersion' => ['getVersion'],
            'getDetails' => ['getDetails'],
        ];
    }

    /**
     * @dataProvider providerTestGetters
     */
    public function testNotFalse($method)
    {
        $this->assertNotFalse(self::$data->$method());
    }

    public function testThumbnail()
    {
        $this->assertContains('.jpg', self::$data->getThumbnail());
    }

    public function testThumbnailSSL()
    {
        $this->assertContains('https://', self::$data->getThumbnail(380, true));
    }

    public function testDetailTitle()
    {
        $details = self::$data->getDetails();
        $firstDetail = $details[0];
        $this->assertNotEmpty($firstDetail->getTitle());
    }

    public function testDetailBody()
    {
        $details = self::$data->getDetails();
        $firstDetail = $details[0];
        $this->assertNotEmpty($firstDetail->getBody());
    }

    public function testHasEpub()
    {
        // probably true!
        $this->assertTrue(self::$data->hasEpub());
    }

    public function testHasPdf()
    {
        // should always be true
        $this->assertTrue(self::$data->hasPdf());
    }

    /**
     * @expectedException \Bookboon\Api\Exception\EntityDataException
     */
    public function testInvalidBook()
    {
        $book = new PdfBook(['blah']);
    }

    public function testBookDownloadOauth()
    {
        $url = Book::getDownloadUrl(self::$bookboon, 'db98ac1b-435f-456b-9bdd-a2ba00d41a58', ['handle' => 'phpunit']);
        $this->assertContains('/download/', $url);
    }

    public function testBookDownloadBasic()
    {
        $bookboon = new Bookboon(new BasicAuthClient(\Helpers::getApiId(), \Helpers::getApiSecret(), new Headers()));
        $url = Book::getDownloadUrl($bookboon, 'db98ac1b-435f-456b-9bdd-a2ba00d41a58', ['handle' => 'phpunit']);
        $this->assertContains('/download/', $url);
    }

    public function testGetSearch()
    {
        // choose a query with almost certain response;
        $search = Book::search(self::$bookboon, 'engineering')->getEntityStore()->get();
        $this->assertCount(10, $search);
    }

    public function testGetRecommendations()
    {
        $bResponse = Book::recommendations(self::$bookboon);

        $this->assertCount(5, $bResponse->getEntityStore()->get());
    }

    public function testGetRecommendationsSpecific()
    {
        $recommendations = Book::recommendations(self::$bookboon, ['3bf58559-034f-4676-bb5f-a2c101015a58'], 8)->getEntityStore()->get();
        $this->assertCount(8, $recommendations);
    }
}
