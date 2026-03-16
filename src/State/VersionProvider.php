<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Version;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class VersionProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire('%env(APP_VERSION)%')]
        private string $appVersion,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Version
    {
        $version = new Version();
        $version->version = $this->appVersion;
        return $version;
    }
}
