<?php

namespace App\Enums;

enum TeamPermission: string
{
    case ViewTeam = 'team:view';
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    case ViewWorkspace = 'workspace:view';
    case CreateWorkspace = 'workspace:create';
    case ManageWorkspace = 'workspace:manage';

    case ViewProject = 'project:view';
    case CreateProject = 'project:create';
    case UpdateOwnProject = 'project:update-own';
    case DeleteOwnProject = 'project:delete-own';
    case DeployOwnProject = 'project:deploy-own';
    case ShareOwnProject = 'project:share-own';
}
