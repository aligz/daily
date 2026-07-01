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
        $featureRequests = FeatureRequest::with(['user', 'division'])
            ->latest()
            ->get();

        $divisions = Division::all();

        return Inertia::render('feature-requests/Index', [
            'featureRequests' => $featureRequests,
            'divisions' => $divisions,
        ]);
    }

    public function create()
    {
        $divisions = Division::all();

        return Inertia::render('feature-requests/Create', [
            'divisions' => $divisions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'requester_name' => 'required|string|max:255',
            'requester_email' => 'nullable|email|max:255',
            'requester_phone' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'deadline' => 'required|date|after_or_equal:today',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'new';

        FeatureRequest::create($validated);

        return redirect()->route('feature-requests.index');
    }

    public function show(FeatureRequest $featureRequest)
    {
        $featureRequest->load(['user', 'division']);

        return Inertia::render('feature-requests/Show', [
            'featureRequest' => $featureRequest,
        ]);
    }

    public function edit(FeatureRequest $featureRequest)
    {
        $featureRequest->load(['division']);
        $divisions = Division::all();

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
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:new,planning,development,done,released',
            'deadline' => 'sometimes|date|after_or_equal:today',
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
