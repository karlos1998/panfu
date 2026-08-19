<?php

namespace Tests\Unit\Amf;

use App\Infrastructure\Amf\AmfDecoder;
use App\Infrastructure\Amf\AmfEncoder;
use App\Infrastructure\Amf\AmfEnvelope;
use App\Infrastructure\Amf\AmfException;
use App\Infrastructure\Amf\AmfMessage;
use PHPUnit\Framework\TestCase;

class AmfDecoderLimitsTest extends TestCase
{
    public function test_it_rejects_too_many_messages_before_reading_their_bodies(): void
    {
        $this->expectException(AmfException::class);
        $this->expectExceptionMessage('too many messages');

        (new AmfDecoder)->decode(pack('nnn', 0, 0, 33));
    }

    public function test_it_rejects_too_many_headers_before_reading_their_bodies(): void
    {
        $this->expectException(AmfException::class);
        $this->expectExceptionMessage('too many headers');

        (new AmfDecoder)->decode(pack('nn', 0, 17));
    }

    public function test_it_rejects_collections_over_the_entry_limit(): void
    {
        $payload = (new AmfEncoder)->encode(new AmfEnvelope(0, [
            new AmfMessage('service.method', '/1', array_fill(0, 10_001, null)),
        ]));

        $this->expectException(AmfException::class);
        $this->expectExceptionMessage('entry limit');

        (new AmfDecoder)->decode($payload);
    }

    public function test_it_rejects_values_nested_beyond_the_depth_limit(): void
    {
        $value = null;
        for ($depth = 0; $depth < 65; $depth++) {
            $value = [$value];
        }
        $payload = (new AmfEncoder)->encode(new AmfEnvelope(0, [
            new AmfMessage('service.method', '/1', $value),
        ]));

        $this->expectException(AmfException::class);
        $this->expectExceptionMessage('nesting limit');

        (new AmfDecoder)->decode($payload);
    }

    public function test_it_rejects_trailing_bytes_and_oversized_payloads(): void
    {
        $valid = (new AmfEncoder)->encode(new AmfEnvelope(0, [
            new AmfMessage('service.method', '/1', []),
        ]));

        try {
            (new AmfDecoder)->decode($valid."\0");
            $this->fail('Trailing bytes were accepted.');
        } catch (AmfException $exception) {
            $this->assertStringContainsString('trailing', $exception->getMessage());
        }

        $this->expectException(AmfException::class);
        $this->expectExceptionMessage('size limit');
        (new AmfDecoder)->decode(str_repeat('x', 1_048_577));
    }

    public function test_every_truncated_prefix_of_a_valid_packet_fails_cleanly(): void
    {
        $payload = (new AmfEncoder)->encode(new AmfEnvelope(3, [
            new AmfMessage('amfConnectionService.ping', '/1', ['text', 123, true]),
        ]));

        for ($length = 0; $length < strlen($payload); $length++) {
            try {
                (new AmfDecoder)->decode(substr($payload, 0, $length));
                $this->fail("Truncated AMF payload of {$length} bytes was accepted.");
            } catch (AmfException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
