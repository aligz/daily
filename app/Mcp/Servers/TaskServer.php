<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetTaskTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Task Server')]
#[Version('0.0.1')]
#[Instructions('Instructions describing how to use the server and its features.')]
class TaskServer extends Server
{
    protected array $tools = [
        GetTaskTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
