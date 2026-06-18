<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\DeployProjectTool;
use App\Mcp\Tools\FetchProjectTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('MCP Server')]
#[Version('0.1.0')]
#[Instructions('Use fetch-project to read an existing hosted project by URL before making changes. Use deploy-project without a url to create and publish a new personal static project. Use deploy-project with an existing hosted project url to update that project by publishing a full replacement file set.')]
class MCPServer extends Server
{
    protected array $tools = [
        DeployProjectTool::class,
        FetchProjectTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
