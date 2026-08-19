<?php

namespace Tests\Unit\Panfu;

use PHPUnit\Framework\TestCase;

class FurnitureDataVOTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__, 3).'/information-server/ClassLoader.php';
        require_once dirname(__DIR__, 3).'/information-server/Services/Vo/FurnitureDataVO.php';
    }

    public function test_it_prefers_the_room_property_used_by_the_flash_client(): void
    {
        $furniture = new \FurnitureDataVO;
        $furniture->room = 100871;
        $furniture->roomID = 0;

        $this->assertSame(100871, $furniture->resolvedRoomID());
    }

    public function test_it_still_accepts_the_legacy_room_id_property(): void
    {
        $furniture = new \FurnitureDataVO;
        $furniture->roomID = 100870;

        $this->assertSame(100870, $furniture->resolvedRoomID());
    }

    public function test_the_flash_room_property_is_present_in_the_amf_value_object(): void
    {
        $furniture = new \FurnitureDataVO;
        $furniture->room = 100871;

        $packet = new \Amfphp_Core_Amf_Packet;
        $packet->messages[] = new \Amfphp_Core_Amf_Message('/1/onResult', '', $furniture);
        $payload = (new \Amfphp_Core_Amf_Serializer)->serialize($packet);

        $this->assertStringContainsString(pack('n', 4).'room', $payload);
    }
}
