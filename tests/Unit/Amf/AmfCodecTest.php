<?php

namespace Tests\Unit\Amf;

use App\Infrastructure\Amf\AmfDecoder;
use App\Infrastructure\Amf\AmfEncoder;
use App\Infrastructure\Amf\TypedObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AmfCodecTest extends TestCase
{
    #[DataProvider('legacyPayloads')]
    public function test_it_decodes_and_reencodes_legacy_amfphp_payloads(string $payload): void
    {
        $binary = base64_decode($payload, true);
        $this->assertIsString($binary);

        $envelope = (new AmfDecoder)->decode($binary);
        $encoded = (new AmfEncoder)->encode($envelope);
        $decodedAgain = (new AmfDecoder)->decode($encoded);

        $this->assertEquals($envelope, $decodedAgain);
    }

    public function test_it_preserves_amf0_typed_login_arguments(): void
    {
        $envelope = (new AmfDecoder)->decode(base64_decode(self::LOGIN_AMF0));
        $message = $envelope->messages[0];

        $this->assertSame('amfConnectionService.doLogin', $message->target);
        $this->assertSame('/1', $message->response);
        $this->assertIsArray($message->data);
        $this->assertInstanceOf(TypedObject::class, $message->data[0]);
        $this->assertSame('com.pandaland.mvc.model.vo.LoginVO', $message->data[0]->type);
        $this->assertSame('Panda', $message->data[0]->get('playerName'));
        $this->assertSame('secret', $message->data[0]->get('pw'));
    }

    public function test_it_preserves_amf3_typed_furniture_arguments(): void
    {
        $envelope = (new AmfDecoder)->decode(base64_decode(self::FURNITURE_AMF3));
        $furniture = $envelope->messages[0]->data[0][0];

        $this->assertSame(3, $envelope->encoding);
        $this->assertInstanceOf(TypedObject::class, $furniture);
        $this->assertSame('com.pandaland.mvc.model.vo.FurnitureDataVO', $furniture->type);
        $this->assertSame(12, $furniture->get('x'));
        $this->assertSame(3, $furniture->get('room'));
        $this->assertTrue($furniture->get('active'));
    }

    /** @return iterable<string, array{string}> */
    public static function legacyPayloads(): iterable
    {
        yield 'AMF0 ping' => [self::PING_AMF0];
        yield 'AMF3 ping' => [self::PING_AMF3];
        yield 'AMF0 login' => [self::LOGIN_AMF0];
        yield 'AMF3 login' => [self::LOGIN_AMF3];
        yield 'AMF3 furniture' => [self::FURNITURE_AMF3];
    }

    private const PING_AMF0 = 'AAAAAAABABlhbWZDb25uZWN0aW9uU2VydmljZS5waW5nAAIvMQAAAAUKAAAAAA==';

    private const PING_AMF3 = 'AAAAAAABABlhbWZDb25uZWN0aW9uU2VydmljZS5waW5nAAIvMQAAAAQRCQEB';

    private const LOGIN_AMF0 = 'AAAAAAABABxhbWZDb25uZWN0aW9uU2VydmljZS5kb0xvZ2luAAIvMQAAAFsKAAAAARAAImNvbS5wYW5kYWxhbmQubXZjLm1vZGVsLnZvLkxvZ2luVk8AAmlkAAAAAAAAAAAAAApwbGF5ZXJOYW1lAgAFUGFuZGEAAnB3AgAGc2VjcmV0AAAJ';

    private const LOGIN_AMF3 = 'AAAAAAABABxhbWZDb25uZWN0aW9uU2VydmljZS5kb0xvZ2luAAIvMQAAACoRCQMBCgsBBWlkBAAVcGxheWVyTmFtZQYLUGFuZGEFcHcGDXNlY3JldAE=';

    private const FURNITURE_AMF3 = 'AAAAAAABACFhbWZQbGF5ZXJTZXJ2aWNlLnVwZGF0ZUZ1cm5pdHVyZXMAAi8yAAAAjBEJAwEJAwEKgUNVY29tLnBhbmRhbGFuZC5tdmMubW9kZWwudm8uRnVybml0dXJlRGF0YVZPFXBhcmFtZXRlcnMDeAN5B3JvdAd1aWQFaWQJdHlwZQ1hY3RpdmUPcHJlbWl1bQ1ib3VnaHQJcm9vbQ1yb29tSUQBBAwEIgQCBE0EZAYFMDADAgMEAwQD';
}
