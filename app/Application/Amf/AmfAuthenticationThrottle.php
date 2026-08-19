<?php

namespace App\Application\Amf;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class AmfAuthenticationThrottle
{
    public function __construct(private readonly Request $request) {}

    public function blocked(string $identifier): bool
    {
        return RateLimiter::tooManyAttempts($this->identityKey($identifier), $this->identityLimit())
            || RateLimiter::tooManyAttempts($this->ipKey(), $this->ipLimit());
    }

    public function failed(string $identifier): void
    {
        RateLimiter::hit($this->identityKey($identifier), 60);
        RateLimiter::hit($this->ipKey(), 60);
    }

    public function succeeded(string $identifier): void
    {
        RateLimiter::clear($this->identityKey($identifier));
    }

    public function registrationBlocked(): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->registrationKey(),
            max(1, (int) config('panfu.amf.registrations_per_minute', 5)),
        );
    }

    public function registrationAttempted(): void
    {
        RateLimiter::hit($this->registrationKey(), 60);
    }

    private function identityKey(string $identifier): string
    {
        return 'amf-login:identity:'.hash('sha256', $this->request->ip().'|'.mb_strtolower($identifier));
    }

    private function ipKey(): string
    {
        return 'amf-login:ip:'.hash('sha256', $this->request->ip());
    }

    private function registrationKey(): string
    {
        return 'amf-registration:ip:'.hash('sha256', $this->request->ip());
    }

    private function identityLimit(): int
    {
        return max(1, (int) config('panfu.amf.login_attempts_per_minute', 5));
    }

    private function ipLimit(): int
    {
        return max($this->identityLimit(), (int) config('panfu.amf.login_attempts_per_ip', 20));
    }
}
