<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;

readonly class MatterpipeRuntimeContext
{
    public function __construct(
        public Project $project,
        public User $user,
        public bool $canWrite,
    ) {
        //
    }
}
