<?php

namespace Fedale\AccessControlVoterBundle\Config;

final readonly class VoterConfig
{
    public function __construct(
        public bool $enabled,
        public string $superAdminRole,
    ) {
    }
}
