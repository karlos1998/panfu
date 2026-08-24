<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Infrastructure\Amf\TypedObject;
use App\Models\GoldPackageCode;
use Illuminate\Support\Facades\DB;

final class ActivateGoldPackageAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly PlayerSession $session,
    ) {}

    public function activateGoldPackage(string $code): TypedObject
    {
        $player = $this->session->player();
        $normalizedCode = strtoupper(preg_replace('/[\s-]+/', '', trim($code)) ?? '');
        if ($player === null || strlen($normalizedCode) < 6 || strlen($normalizedCode) > 64) {
            return $this->responses->make(1, 'Invalid activation code.');
        }

        $activated = DB::transaction(function () use ($normalizedCode, $player): bool {
            $activation = GoldPackageCode::query()
                ->where('code_hash', hash('sha256', $normalizedCode))
                ->lockForUpdate()
                ->first();
            if ($activation === null || $activation->redeemed_at !== null) {
                return false;
            }

            $activation->forceFill([
                'redeemed_by' => $player->getKey(),
                'redeemed_at' => now(),
            ])->save();
            $player->forceFill(['goldpanda' => 1])->save();

            return true;
        });

        return $activated
            ? $this->responses->make(message: 'Gold Panda activated.')
            : $this->responses->make(1, 'Invalid or already used activation code.');
    }
}
