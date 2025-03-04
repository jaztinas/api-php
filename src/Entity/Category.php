<?php

namespace Bookboon\Api\Entity;

use Bookboon\Api\Bookboon;
use Bookboon\Api\Client\BookboonResponse;
use Bookboon\Api\Client\ClientInterface;

class Category extends Entity
{
    const TEXTBOOKS = 'd1fabb36-4eff-4760-a80d-a15700efa9ae';
    const BUSINESS = '82403e77-ccbf-4e10-875c-a15700ef8a56';

    /**
     * Get Category.
     *
     * @param Bookboon $bookboon
     * @param string $categoryId
     * @param array $bookTypes
     * @return BookboonResponse
     * @throws \Bookboon\Api\Exception\ApiDecodeException
     * @throws \Bookboon\Api\Exception\EntityDataException
     * @throws \Bookboon\Api\Exception\UsageException
     */
    public static function get(Bookboon $bookboon, string $categoryId, array $bookTypes = ['pdf']) : BookboonResponse
    {
        $bResponse = $bookboon->rawRequest("/categories/$categoryId", ['bookType' => join(',', $bookTypes)]);

        $bResponse->setEntityStore(
            new EntityStore(
                [
                    new static($bResponse->getReturnArray())
                ]
            )
        );

        return $bResponse;
    }

    /**
     * Returns the entire Category structure.
     *
     * @param Bookboon $bookboon
     * @param array $blacklistedCategoryIds
     * @param int $depth level of recursion (default 2 maximum, 0 no recursion)
     * @return BookboonResponse
     * @throws \Bookboon\Api\Exception\UsageException
     */
    public static function getTree(
        Bookboon $bookboon,
        array $blacklistedCategoryIds = [],
        int $depth = 2
    ) : BookboonResponse {
        $bResponse = $bookboon->rawRequest('/categories', ['depth' => $depth]);

        $categories = $bResponse->getReturnArray();

        if (count($blacklistedCategoryIds) !== 0) {
            self::recursiveBlacklist($categories, $blacklistedCategoryIds);
        }

        $bResponse->setEntityStore(
            new EntityStore(Category::getEntitiesFromArray($categories))
        );

        return $bResponse;
    }

    /**
     * @param array $categories
     * @param array $blacklistedCategoryIds
     */
    private static function recursiveBlacklist(array &$categories, array $blacklistedCategoryIds) : void
    {
        $hasAlteredArray = false;

        foreach ($categories as $key => $category) {
            if (in_array($category['_id'], $blacklistedCategoryIds, true)) {
                unset($categories[$key]);
                $hasAlteredArray = true;
                continue;
            }
            if (isset($category['categories'])) {
                self::recursiveBlacklist($categories[$key]['categories'], $blacklistedCategoryIds);
            }
        }

        if ($hasAlteredArray) {
            $categories = array_values($categories);
        }
    }

    /**
     * Get the download url.
     *
     * @param Bookboon $bookboon
     * @param string $categoryId
     * @param array $variables
     * @return string
     */
    public static function getDownloadUrl(Bookboon $bookboon, string $categoryId, array $variables) : string
    {
        $bResponse = $bookboon->rawRequest("/categories/$categoryId/download", $variables, ClientInterface::HTTP_POST);

        return $bResponse->getReturnArray()['url'];
    }

    protected function isValid(array $array) : bool
    {
        return isset($array['_id'], $array['name'], $array['description'], $array['homepage']);
    }

    /**
     * @return string UUID of entity
     */
    public function getId()
    {
        return $this->safeGet('_id');
    }

    /**
     * @return string slug
     */
    public function getSlug()
    {
        return $this->safeGet('_slug');
    }

    /**
     * @return string name of entity
     */
    public function getName()
    {
        return $this->safeGet('name');
    }

    /**
     * @return string title of entity
     */
    public function getTitle()
    {
        return $this->safeGet('title');
    }

    /**
     * @return string seo title of entity
     */
    public function getSeoTitle()
    {
        return $this->safeGet('seoTitle');
    }

    /**
     * @return string link to category on bookboon.com
     */
    public function getHomepage()
    {
        return $this->safeGet('homepage');
    }

    /**
     * @return string category description
     */
    public function getDescription()
    {
        return $this->safeGet('description');
    }

    /**
     * @return Category[] of Category objects
     */
    public function getCategories() : array
    {
        return self::getEntitiesFromArray($this->safeGet('categories', []));
    }

    /**
     * @return Book[] books in category
     */
    public function getBooks() : array
    {
        return Book::getEntitiesFromArray($this->safeGet('books', []));
    }
}
