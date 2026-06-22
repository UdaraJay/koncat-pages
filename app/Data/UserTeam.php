<?php

namespace App\Data;

readonly class UserTeam
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $subdomain,
        public bool $isPersonal,
        public ?string $role,
        public ?string $roleLabel,
        public bool $canUpdateTeam,
        public ?bool $isCurrent = null,
    ) {
        //
    }
}
