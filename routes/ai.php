<?php

use App\Mcp\Servers\FeatureRequestServer;
use App\Mcp\Servers\TaskServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/tasks', TaskServer::class);
Mcp::web('/mcp/feature-requests', FeatureRequestServer::class);
