<?php

namespace Tests\Unit\Panfu;

use App\Application\Amf\ValueObjectFactory;
use App\Infrastructure\Amf\AmfEncoder;
use App\Infrastructure\Amf\AmfEnvelope;
use App\Infrastructure\Amf\AmfMessage;
use PHPUnit\Framework\TestCase;

class FurnitureDataVOTest extends TestCase
{
    public function test_the_flash_room_property_is_present_in_the_amf_value_object(): void
    {
        $furniture = (new ValueObjectFactory)->make('FurnitureData', [
            'room' => 100871,
            'roomID' => 100871,
        ]);
        $payload = (new AmfEncoder)->encode(new AmfEnvelope(messages: [
            new AmfMessage('/1/onResult', '', $furniture),
        ]));

        $this->assertStringContainsString(pack('n', 4).'room', $payload);
        $this->assertSame(100871, $furniture->get('room'));
        $this->assertSame(100871, $furniture->get('roomID'));
    }
}
