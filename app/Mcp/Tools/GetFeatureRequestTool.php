<?php

namespace App\Mcp\Tools;

use App\Models\FeatureRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetFeatureRequestTool extends Tool
{
    protected string $description = 'Retrieve feature requests from the database. Supports filtering by ID, status, division, priority, keyword search, overdue status, and limiting results.';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', 'in:new,planning,development,done,released'],
            'division_id' => ['sometimes', 'integer', 'min:1'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'search' => ['sometimes', 'string', 'max:255'],
            'overdue' => ['sometimes', 'boolean'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        if (! empty($validated['id'])) {
            $request = FeatureRequest::with(['user', 'division'])->find($validated['id']);

            if (! $request) {
                return Response::error('Feature request not found.');
            }

            $data = $request->toArray();
            $data['is_overdue'] = $request->deadline->isPast() && $request->status !== 'released';

            return Response::json($data);
        }

        $query = FeatureRequest::query();

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['division_id'])) {
            $query->where('division_id', $validated['division_id']);
        }

        if (! empty($validated['priority'])) {
            $query->where('priority', $validated['priority']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%");
            });
        }

        if (isset($validated['overdue']) && $validated['overdue'] === true) {
            $query->where('deadline', '<', now())
                ->where('status', '!=', 'released');
        }

        $limit = $validated['limit'] ?? 15;

        $featureRequests = $query
            ->with(['user', 'division'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $data = $item->toArray();
                $data['is_overdue'] = $item->deadline->isPast() && $item->status !== 'released';

                return $data;
            });

        return Response::json($featureRequests->toArray());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Filter by a specific feature request ID. When provided, other filters are ignored.')
                ->nullable(),
            'status' => $schema->string()
                ->description('Filter by status: new, planning, development, done, released.')
                ->nullable(),
            'division_id' => $schema->integer()
                ->description('Filter by division ID.')
                ->nullable(),
            'priority' => $schema->string()
                ->description('Filter by priority: low, medium, high, urgent.')
                ->nullable(),
            'search' => $schema->string()
                ->description('Search keyword that matches title, description, or requester name.')
                ->nullable(),
            'overdue' => $schema->boolean()
                ->description('Filter only overdue requests (deadline passed and not yet released).')
                ->nullable(),
            'limit' => $schema->integer()
                ->description('Maximum number of feature requests to return (1-100). Defaults to 15.')
                ->nullable(),
        ];
    }
}
