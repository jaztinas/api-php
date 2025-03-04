<?php

namespace Bookboon\Api\Entity;

use Bookboon\Api\Bookboon;
use PHPUnit\Framework\TestCase;

/**
 * Class CategoryTest
 * @package Bookboon\Api\Entity
 * @group entity
 */
class CategoryTest extends TestCase
{
    private static $data = null;
    private static $bookboon = null;

    public static function setUpBeforeClass()
    {
        include_once(__DIR__ . '/../Helpers.php');
        self::$bookboon = \Helpers::getBookboon();
        self::$data = Category::get(self::$bookboon, '062adfac-844b-4e8c-9242-a1620108325e')
            ->getEntityStore()
            ->getSingle();
    }

    public function testGetId()
    {
        $this->assertEquals('062adfac-844b-4e8c-9242-a1620108325e', self::$data->getId());
    }

    public function providerTestGetters()
    {
        return [
            'getName' => ['getName'],
            'getHomepage' => ['getHomepage'],
            'getDescription' => ['getDescription'],
            'getBooks' => ['getBooks'],
            'getCategories' => ['getCategories'],
        ];
    }

    /**
     * @dataProvider providerTestGetters
     */
    public function testNotFalse($method)
    {
        $this->assertNotFalse(self::$data->$method());
    }

    /**
     * @expectedException \Bookboon\Api\Exception\EntityDataException
     */
    public function testInvalidCategory()
    {
        $category = new Category(['blah']);
    }

    public function testGetCategoryTree()
    {
        $categories = Category::getTree(self::$bookboon)->getEntityStore()->get();
        $this->assertEquals(2, count($categories));
    }

    public function testGetCategoryTreeBlacklist()
    {
        $categories = Category::getTree(self::$bookboon, ['82403e77-ccbf-4e10-875c-a15700ef8a56', '07651831-1c44-4815-87a2-a2b500f5934a']);

        $this->assertEquals(1, count($categories->getEntityStore()->get()));
    }

    public function testCategoryDownload()
    {
        $url = Category::getDownloadUrl(self::$bookboon, '062adfac-844b-4e8c-9242-a1620108325e', ['handle' => 'phpunit']);
        $this->assertContains('/download/', $url);
    }
}
