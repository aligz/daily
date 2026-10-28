<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\FeatureRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeatureRequestController extends Controller
{
    public function index()
    {
        $featureRequests = FeatureRequest::with(['user:id,name', 'division:id,name'])
            ->latest()
            ->get();

        $divisions = Division::orderBy('name')->get(['id', 'name']);

        return Inertia::render('feature-requests/Index', [
            'featureRequests' => $featureRequests,
            'divisions' => $divisions,
        ]);
    }

    public function create()
    {
        $divisions = Division::orderBy('name')->get(['id', 'name']);

        return Inertia::render('feature-requests/Create', [
            'divisions' => $divisions,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $rules = [
            'division_id' => 'required|exists:divisions,id',
            'requester_name' => 'required|string|max:255',
            'requester_email' => 'nullable|email|max:255',
            'requester_phone' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'change_reason' => 'required|string',
            'proposed_action' => 'required|string',
            'effect_on_organization' => 'required|string',
            'effect_of_not_approving' => 'required|string',
        ];

        // Only signed-in users triage the request; division users never see
        // these fields, and anything they inject is ignored below.
        if ($user) {
            $rules['priority'] = 'required|in:low,medium,high,urgent';
            $rules['deadline'] = 'required|date|after_or_equal:today';
        }

        $validated = $request->validate($rules);

        $validated['user_id'] = $user?->id;
        $validated['status'] = 'new';
        $validated['priority'] ??= null;
        $validated['deadline'] ??= null;

        $featureRequest = FeatureRequest::create($validated);

        return redirect()->route('feature-requests.show', $featureRequest);
    }

    public function show(FeatureRequest $featureRequest)
    {
        $featureRequest->load(['user:id,name', 'division:id,name']);

        return Inertia::render('feature-requests/Show', [
            'featureRequest' => $featureRequest,
            'isPublic' => $featureRequest->user_id === null,
        ]);
    }

    public function edit(FeatureRequest $featureRequest)
    {
        $featureRequest->load(['division']);
        $divisions = Division::orderBy('name')->get(['id', 'name']);

        return Inertia::render('feature-requests/Edit', [
            'featureRequest' => $featureRequest,
            'divisions' => $divisions,
        ]);
    }

    public function update(Request $request, FeatureRequest $featureRequest)
    {
        $validated = $request->validate([
            'division_id' => 'sometimes|exists:divisions,id',
            'requester_name' => 'sometimes|string|max:255',
            'requester_email' => 'nullable|email|max:255',
            'requester_phone' => 'nullable|string|max:50',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'change_reason' => 'nullable|string',
            'proposed_action' => 'nullable|string',
            'effect_on_organization' => 'nullable|string',
            'effect_of_not_approving' => 'nullable|string',
            'priority' => 'sometimes|nullable|in:low,medium,high,urgent',
            'status' => 'sometimes|in:new,planning,development,done,released',
            'deadline' => 'sometimes|nullable|date',
            'notes' => 'nullable|string',
            'released_at' => 'nullable|date',
        ]);

        // Auto-fill released_at when status changes to released
        if (isset($validated['status'])) {
            if ($validated['status'] === 'released' && $featureRequest->status !== 'released') {
                // Moving to released — auto fill if not provided
                if (empty($validated['released_at'])) {
                    $validated['released_at'] = now();
                }
            } elseif ($validated['status'] !== 'released' && $featureRequest->status === 'released') {
                // Moving away from released — clear released_at
                $validated['released_at'] = null;
            }
        }

        $featureRequest->update($validated);

        return redirect()->back();
    }

    public function destroy(FeatureRequest $featureRequest)
    {
        $featureRequest->delete();

        return redirect()->route('feature-requests.index');
    }
}
