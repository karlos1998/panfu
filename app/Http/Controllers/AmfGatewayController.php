<?php

namespace App\Http\Controllers;

use App\Application\Amf\AmfGateway;
use App\Infrastructure\Amf\AmfException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class AmfGatewayController extends Controller
{
    public function __construct(private readonly AmfGateway $gateway) {}

    public function __invoke(Request $request, ?string $path = null): Response
    {
        $contentType = strtolower(trim(explode(';', (string) $request->header('Content-Type'))[0]));
        if ($contentType !== 'application/x-amf') {
            return response('AMF content type required.', Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        $payload = $request->getContent();
        if (strlen($payload) > (int) config('panfu.amf.max_payload_bytes', 1_048_576)) {
            return response('AMF payload is too large.', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        try {
            $response = $this->gateway->handle($payload);
        } catch (AmfException) {
            return response('Invalid AMF payload.', Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            report($exception);

            return response('Could not process AMF payload.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response($response)
            ->header('Content-Type', 'application/x-amf')
            ->header('Cache-Control', 'no-store')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
