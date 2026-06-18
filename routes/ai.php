<?php

use App\Mcp\Servers\MCPServer;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Passport\Http\Middleware\CheckToken;

Mcp::oauthRoutes();

Mcp::web('/mcp', MCPServer::class)
    ->middleware(['auth:api', CheckToken::using('mcp:use')]);
