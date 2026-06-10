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
        $validated['status'] = 'baru';

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

    public function update(Request $request, FeatureRequest $featureRequest)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:baru,review,diacc,diproses,selesai',
            'notes' => 'nullable|string',
        ]);

        $featureRequest->update($validated);

        return redirect()->back();
    }

    public function destroy(FeatureRequest $featureRequest)
    {
        $featureRequest->delete();

        return redirect()->route('feature-requests.index');
    }
}
