<?php

namespace App\Enums;

enum WorkspacePermission: string
{
    case UpdateWorkspace = 'workspace:update';
    case DeleteWorkspace = 'workspace:delete';
    case AddMember = 'workspace-member:add';
    case UpdateMember = 'workspace-member:update';
    case RemoveMember = 'workspace-member:remove';
    case CreateProject = 'project:create';
    case UpdateProject = 'project:update';
    case DeleteProject = 'project:delete';
    case DeployProject = 'project:deploy';
}
