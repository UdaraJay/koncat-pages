<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\DeployProjectTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('MCP Server')]
#[Version('0.1.0')]
#[Instructions('Create and publish hosted static projects from inline file payloads.')]
class MCPServer extends Server
{
    protected array $tools = [
        DeployProjectTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
