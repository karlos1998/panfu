<?php

namespace App\Application\Amf;

use App\Application\Amf\Services\ActionAmfService;
use App\Application\Amf\Services\BuddyFilterAmfService;
use App\Application\Amf\Services\BuddyListAmfService;
use App\Application\Amf\Services\ConnectionAmfService;
use App\Application\Amf\Services\GameAmfService;
use App\Application\Amf\Services\LanguageAmfService;
use App\Application\Amf\Services\PetAmfService;
use App\Application\Amf\Services\PlayerAmfService;
use App\Application\Amf\Services\ProfileAmfService;
use App\Application\Amf\Services\RegistrationAmfService;
use App\Application\Amf\Services\SocialHighscoreAmfService;
use App\Infrastructure\Amf\AmfException;
use Illuminate\Contracts\Container\Container;
use ReflectionMethod;

final class AmfServiceRegistry
{
    /** @var array<string, class-string> */
    private const SERVICES = [
        'amfActionService' => ActionAmfService::class,
        'amfBuddyFilterService' => BuddyFilterAmfService::class,
        'amfBuddyListService' => BuddyListAmfService::class,
        'amfConnectionService' => ConnectionAmfService::class,
        'amfGameService' => GameAmfService::class,
        'amfLanguageService' => LanguageAmfService::class,
        'amfPetService' => PetAmfService::class,
        'amfPlayerService' => PlayerAmfService::class,
        'amfProfileService' => ProfileAmfService::class,
        'amfRegistrationService' => RegistrationAmfService::class,
        'amfSocialHighscoreService' => SocialHighscoreAmfService::class,
    ];

    public function __construct(private readonly Container $container) {}

    /** @param list<mixed> $parameters */
    public function call(string $target, array $parameters): mixed
    {
        $parts = preg_split('~[./]~', $target) ?: [];
        $method = array_pop($parts);
        $serviceName = array_pop($parts);
        $class = is_string($serviceName) ? self::SERVICES[$serviceName] ?? null : null;

        if ($class === null || ! is_string($method) || str_starts_with($method, '__')) {
            throw new AmfException("Unknown AMF target: {$target}");
        }

        $service = $this->container->make($class);
        if (! method_exists($service, $method)) {
            throw new AmfException("Unknown AMF method: {$target}");
        }

        $reflection = new ReflectionMethod($service, $method);
        if (! $reflection->isPublic()) {
            throw new AmfException("AMF method is not public: {$target}");
        }

        return $service->{$method}(...$parameters);
    }
}
