<?php

namespace Tests\Unit\Amf;

use App\Infrastructure\Amf\AmfDecoder;
use App\Infrastructure\Amf\AmfException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AmfMalformedReferenceTest extends TestCase
{
    #[DataProvider('invalidBodies')]
    public function test_invalid_markers_and_references_fail_with_a_protocol_exception(string $body, string $message): void
    {
        $this->expectException(AmfException::class);
        $this->expectExceptionMessage($message);

        (new AmfDecoder)->decode($this->envelope($body));
    }

    /** @return iterable<string, array{string,string}> */
    public static function invalidBodies(): iterable
    {
        yield 'unsupported AMF0 marker' => ["\x04", 'Unsupported AMF0 marker'];
        yield 'missing AMF0 object reference' => ["\x07\x00\x00", 'Invalid AMF0 reference'];
        yield 'unsupported AMF3 marker' => ["\x11\x7F", 'Unsupported AMF3 marker'];
        yield 'missing AMF3 string reference' => ["\x11\x06\x00", 'Invalid AMF3 string reference'];
        yield 'missing AMF3 date reference' => ["\x11\x08\x00", 'Invalid AMF3 date reference'];
        yield 'missing AMF3 array reference' => ["\x11\x09\x00", 'Invalid AMF3 array reference'];
        yield 'missing AMF3 object reference' => ["\x11\x0A\x00", 'Invalid AMF3 object reference'];
        yield 'missing AMF3 traits reference' => ["\x11\x0A\x01", 'Invalid AMF3 traits reference'];
        yield 'missing AMF3 vector reference' => ["\x11\x0D\x00", 'Invalid AMF3 vector reference'];
    }

    public function test_unsupported_envelope_versions_are_rejected_before_body_processing(): void
    {
        $this->expectException(AmfException::class);
        $this->expectExceptionMessage('Unsupported AMF envelope version');

        (new AmfDecoder)->decode(pack('nnn', 2, 0, 0));
    }

    #[DataProvider('validVectorBodies')]
    public function test_supported_amf3_vector_types_decode_without_loosening_reference_validation(
        string $body,
        array $expected,
    ): void {
        $decoded = (new AmfDecoder)->decode($this->envelope($body))->messages[0]->data;

        $this->assertSame($expected, $decoded);
    }

    /** @return iterable<string, array{string,array<int, mixed>}> */
    public static function validVectorBodies(): iterable
    {
        yield 'signed ints' => ["\x11\x0D\x05\x00".pack('N2', 0xFFFFFFFF, 42), [-1, 42]];
        yield 'unsigned ints' => ["\x11\x0E\x05\x00".pack('N2', 0xFFFFFFFF, 42), [4_294_967_295, 42]];
        yield 'doubles' => ["\x11\x0F\x05\x00".pack('E2', 1.5, -2.25), [1.5, -2.25]];
        yield 'objects' => ["\x11\x10\x05\x00\x01\x04\x01\x04\x02", [1, 2]];
    }

    private function envelope(string $body): string
    {
        $target = 'service.method';
        $response = '/1';

        return pack('nnn', 0, 0, 1)
            .pack('n', strlen($target)).$target
            .pack('n', strlen($response)).$response
            .pack('N', strlen($body)).$body;
    }
}
