<?php

namespace App\Data;

readonly class WorkspacePermissions
{
    public function __construct(
        public bool $canUpdateWorkspace,
        public bool $canDeleteWorkspace,
        public bool $canAddMember,
        public bool $canUpdateMember,
        public bool $canRemoveMember,
        public bool $canCreateProject,
        public bool $canUpdateProject,
        public bool $canDeleteProject,
        public bool $canDeployProject,
    ) {
        //
    }
}
