<?php
namespace Bookboon\Api;

class BookboonTest extends \PHPUnit_Framework_TestCase
{
    private static $API_ID = "";
    private static $API_KEY = "";


    public static function setUpBeforeClass()
    {
        self::$API_ID = getenv('BOOKBOON_API_ID');
        self::$API_KEY = getenv('BOOKBOON_API_KEY');
    }

    /*
     * IP
     */
    public function testInvalidXFFIP()
    {
        $_SERVER["REMOTE_ADDR"] = "127.";
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY);
        $this->assertFalse($bookboon->getHeader(Bookboon::HEADER_XFF));
    }

    public function testValidXFFIP()
    {
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY);
        $this->assertEquals("127.0.0.1", $bookboon->getHeader(Bookboon::HEADER_XFF));
    }

    public function testOverrideXFF()
    {
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY, array(Bookboon::HEADER_XFF => "TEST"));
        $this->assertEquals("TEST", $bookboon->getHeader(Bookboon::HEADER_XFF));
    }

    /*
     * GUID
     */

    public function testInvalidGuid()
    {
        $guid = '4343f-4343-4343';
        $this->assertFalse(Bookboon::isValidGUID($guid));
    }

    public function testValidGuid()
    {
        $guid = 'db98ac1b-435f-456b-9bdd-a2ba00d41a58';
        $this->assertTrue(Bookboon::isValidGUID($guid));
    }

    /*
     * HASH
     */
    public function testHashHeaderXff()
    {
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY, array(Bookboon::HEADER_XFF => "TEST"));
        $bookboon2 = new Bookboon(self::$API_ID, self::$API_KEY, array(Bookboon::HEADER_XFF => "TEST2"));

        $this->assertEquals($bookboon->hash("/test"), $bookboon2->hash("/test"));
    }

    public function testHashHeader()
    {
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY, array(Bookboon::HEADER_XFF => "TEST", Bookboon::HEADER_BRANDING => "branding-test-1"));
        $bookboon2 = new Bookboon(self::$API_ID, self::$API_KEY, array(Bookboon::HEADER_XFF => "TEST2", Bookboon::HEADER_BRANDING => "branding-test-2"));

        $this->assertNotEquals($bookboon->hash("/test"), $bookboon2->hash("/test"));
    }

    public function testHashUrl()
    {
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY);

        $this->assertEquals($bookboon->hash("/test"), $bookboon->hash("/test"));
    }

    /*
     * Actual calls
     */

    /* to test cache properly this must the be first to make calls to the API */
    public function testBookCached()
    {
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY);
        // There's probably no memcache server available on automatic test
        $bookboon->setCache(new Memcached());
        $book = $bookboon->api("/books/db98ac1b-435f-456b-9bdd-a2ba00d41a58");
        $book = $bookboon->api("/books/db98ac1b-435f-456b-9bdd-a2ba00d41a58");
        $this->assertEquals("memcache", Bookboon::$CURL_REQUESTS[1]["curl"]["http_code"]);
    }

    public function testBookDownload()
    {
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY);

        $url = $bookboon->getBookDownloadUrl("db98ac1b-435f-456b-9bdd-a2ba00d41a58", array("handle" => "phpunit"));
        $this->assertContains("/download/", $url);
    }

    public function testCategoryDownload()
    {
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY);

        $url = $bookboon->getCategoryDownloadUrl("062adfac-844b-4e8c-9242-a1620108325e", array("handle" => "phpunit"));
        $this->assertContains("/download/", $url);
    }

    public function testGetSearch()
    {
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY);

        // choose a query with almost certain response;
        $search = $bookboon->getSearch("engineering");
        $this->assertCount(10, $search);
    }

    public function testGetRecommendations()
    {
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY);

        $recommendations = $bookboon->getRecommendations();
        $this->assertCount(5, $recommendations);
    }

    public function testGetRecommendationsSpecific()
    {
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY);

        $recommendations = $bookboon->getRecommendations(array("3bf58559-034f-4676-bb5f-a2c101015a58"), 8);
        $this->assertCount(8, $recommendations);
    }

    /**
     * @expectedException \Bookboon\Api\ApiSyntaxException
     */
    public function testBadUrl()
    {
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY);
        $test = $bookboon->api("bah");
    }

    /**
     * @expectedException \Bookboon\Api\ApiSyntaxException
     */
    public function testBadRequest()
    {
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY);
        $test = $bookboon->api("/search", array("get" => array("q" => "")));
    }

    /**
     * @expectedException \Bookboon\Api\NotFoundException
     */
    public function testNotFound()
    {
        $bookboon = new Bookboon(self::$API_ID, self::$API_KEY);
        $test = $bookboon->api("/bah");
    }

    /**
     * @expectedException \Bookboon\Api\AuthenticationException
     */
    public function testBadAuthentication()
    {
        $bookboon = new Bookboon("badid", "badkey");
        $test = $bookboon->api("/categories/062adfac-844b-4e8c-9242-a1620108325e");
    }

    /**
     * @expectedException \Exception
     */
    public function testEmpty()
    {
        $bookboon = new Bookboon("badid", "");
        $test = $bookboon->api("/categories/062adfac-844b-4e8c-9242-a1620108325e");
    }

}
