<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetFeatureRequestTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Feature Request Server')]
#[Version('0.0.1')]
#[Instructions('Provides access to feature requests. Use this server to query, filter, and retrieve feature requests submitted by internal developers.')]
class FeatureRequestServer extends Server
{
    protected array $tools = [
        GetFeatureRequestTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
