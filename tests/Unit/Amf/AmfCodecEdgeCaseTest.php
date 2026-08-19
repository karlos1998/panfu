<?php

namespace Tests\Unit\Amf;

use App\Infrastructure\Amf\AmfDecoder;
use App\Infrastructure\Amf\AmfEncoder;
use App\Infrastructure\Amf\AmfEnvelope;
use App\Infrastructure\Amf\AmfException;
use App\Infrastructure\Amf\AmfMessage;
use App\Infrastructure\Amf\TypedObject;
use App\Infrastructure\Amf\UndefinedValue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class AmfCodecEdgeCaseTest extends TestCase
{
    public function test_amf0_round_trips_every_supported_value_family(): void
    {
        $anonymous = new stdClass;
        $anonymous->label = 'anonymous';
        $typed = new TypedObject('com.example.Value', ['name' => 'typed', 'enabled' => true]);
        $value = [
            UndefinedValue::instance(),
            null,
            false,
            true,
            42,
            12.5,
            'short',
            str_repeat('x', 65_536),
            ['named' => 'value'],
            $typed,
            $anonymous,
        ];

        $decoded = $this->roundTrip(0, $value);

        $this->assertSame(UndefinedValue::instance(), $decoded[0]);
        $this->assertNull($decoded[1]);
        $this->assertFalse($decoded[2]);
        $this->assertTrue($decoded[3]);
        $this->assertSame(42.0, $decoded[4]);
        $this->assertSame(12.5, $decoded[5]);
        $this->assertSame('short', $decoded[6]);
        $this->assertSame(str_repeat('x', 65_536), $decoded[7]);
        $this->assertSame(['named' => 'value'], $decoded[8]);
        $this->assertInstanceOf(TypedObject::class, $decoded[9]);
        $this->assertSame('com.example.Value', $decoded[9]->type);
        $this->assertSame('typed', $decoded[9]->get('name'));
        $this->assertInstanceOf(stdClass::class, $decoded[10]);
        $this->assertSame('anonymous', $decoded[10]->label);
    }

    #[DataProvider('amf3Integers')]
    public function test_amf3_round_trips_every_integer_encoding_boundary(int $value): void
    {
        $this->assertEquals($value, $this->roundTrip(3, $value));
    }

    /** @return iterable<string, array{int}> */
    public static function amf3Integers(): iterable
    {
        foreach ([-268_435_456, -1, 0, 127, 128, 16_383, 16_384, 2_097_151, 2_097_152, 268_435_455, 268_435_456] as $value) {
            yield (string) $value => [$value];
        }
    }

    public function test_amf3_round_trips_lists_objects_typed_objects_and_repeated_strings(): void
    {
        $decoded = $this->roundTrip(3, [
            'repeated',
            'repeated',
            ['nested', null, true],
            ['named' => 'value'],
            new TypedObject('com.example.Value', ['answer' => 42]),
        ]);

        $this->assertSame('repeated', $decoded[0]);
        $this->assertSame('repeated', $decoded[1]);
        $this->assertSame(['nested', null, true], $decoded[2]);
        $this->assertInstanceOf(stdClass::class, $decoded[3]);
        $this->assertSame('value', $decoded[3]->named);
        $this->assertInstanceOf(TypedObject::class, $decoded[4]);
        $this->assertSame(42, $decoded[4]->get('answer'));
    }

    public function test_unsupported_php_values_fail_explicitly_for_both_encodings(): void
    {
        $resource = fopen('php://memory', 'rb');
        $this->assertIsResource($resource);

        foreach ([0, 3] as $encoding) {
            try {
                (new AmfEncoder)->encode(new AmfEnvelope($encoding, [
                    new AmfMessage('service.method', '/1', $resource),
                ]));
                $this->fail("Encoding {$encoding} accepted a PHP resource.");
            } catch (AmfException $exception) {
                $this->assertStringContainsString('Cannot encode value', $exception->getMessage());
            }
        }

        fclose($resource);
    }

    public function test_typed_object_helpers_and_undefined_singleton_are_stable(): void
    {
        $object = new TypedObject('com.example.Value');

        $this->assertFalse($object->has('answer'));
        $this->assertSame('fallback', $object->get('answer', 'fallback'));
        $this->assertSame($object, $object->set('answer', 42));
        $this->assertTrue($object->has('answer'));
        $this->assertSame(['answer' => 42], $object->properties());
        $this->assertSame([
            'type' => 'com.example.Value',
            'properties' => ['answer' => 42],
        ], $object->jsonSerialize());
        $this->assertSame(UndefinedValue::instance(), UndefinedValue::instance());
    }

    private function roundTrip(int $encoding, mixed $value): mixed
    {
        $payload = (new AmfEncoder)->encode(new AmfEnvelope($encoding, [
            new AmfMessage('service.method', '/1', $value),
        ]));

        return (new AmfDecoder)->decode($payload)->messages[0]->data;
    }
}
