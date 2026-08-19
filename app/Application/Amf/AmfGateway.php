<?php

namespace App\Application\Amf;

use App\Infrastructure\Amf\AmfDecoder;
use App\Infrastructure\Amf\AmfEncoder;
use App\Infrastructure\Amf\AmfEnvelope;
use App\Infrastructure\Amf\AmfMessage;
use stdClass;
use Throwable;

final class AmfGateway
{
    public function __construct(
        private readonly AmfDecoder $decoder,
        private readonly AmfEncoder $encoder,
        private readonly AmfServiceRegistry $services,
    ) {}

    public function handle(string $payload): string
    {
        $request = $this->decoder->decode($payload);
        $responses = [];

        foreach ($request->messages as $message) {
            try {
                $parameters = is_array($message->data) ? array_values($message->data) : [$message->data];
                $data = $this->services->call($message->target, $parameters);
                $responses[] = new AmfMessage($message->response.'/onResult', 'null', $data);
            } catch (Throwable $exception) {
                report($exception);
                $fault = new stdClass;
                $fault->faultCode = $exception->getCode();
                $fault->faultString = $exception->getMessage();
                $fault->faultDetail = '';
                $responses[] = new AmfMessage($message->response.'/onStatus', 'null', $fault);
            }
        }

        return $this->encoder->encode(new AmfEnvelope($request->encoding, $responses));
    }
}
