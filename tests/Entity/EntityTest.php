<?php

namespace Bookboon\Api\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Class EntityTest
 * @package Entity
 * @group entity
 */
class EntityTest extends TestCase
{
    /*
     * UUID
     */
    public function testInvalidUuid()
    {
        $uuid = '4343f-4343-4343';
        $this->assertFalse(Entity::isValidUUID($uuid));
    }

    public function testValidUuid()
    {
        $uuid = 'db98ac1b-435f-456b-9bdd-a2ba00d41a58';
        $this->assertTrue(Entity::isValidUUID($uuid));
    }
}