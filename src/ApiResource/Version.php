<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\VersionProvider;

#[ApiResource(
    shortName: 'Version',
    operations: [
        new Get(
            uriTemplate: '/version',
            provider: VersionProvider::class,
            description: 'Retourne la version actuelle de l\'API.',
        ),
    ],
    description: 'Informations de version de l\'API TechSupport360.',
)]
class Version
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public string $version;
}
