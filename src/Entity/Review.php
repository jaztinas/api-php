<?php

namespace Bookboon\Api\Entity;

class Review extends Entity
{

    protected function isValid(Array $array)
    {
        return isset($array['author'], $array['comment'], $array['created']);
    }

    /**
     * @return string author of review
     */
    public function getAuthor()
    {
        return $this->safeGet("author");
    }

    /**
     * @return string created date
     */
    public function getCreated()
    {
        return $this->safeGet("created");
    }

    /**
     * @return string user inputted comment
     */
    public function getComment()
    {
        return $this->safeGet("comment");
    }
}
