<?php

namespace Fedale\RbacBundle\Config;

final readonly class VoterConfig
{
    public function __construct(
        public bool $enabled,
        public string $superAdminRole,
    ) {
    }
}
