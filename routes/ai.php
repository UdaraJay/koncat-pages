<?php

use App\Http\Middleware\AuthenticateMCPRequest;
use App\Mcp\Servers\MCPServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', MCPServer::class)
    ->middleware(AuthenticateMCPRequest::class);
