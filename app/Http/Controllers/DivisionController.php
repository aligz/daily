<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DivisionController extends Controller
{
    public function index()
    {
        $divisions = Division::withCount('featureRequests')->get();

        return Inertia::render('divisions/Index', [
            'divisions' => $divisions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:divisions,name',
        ]);

        Division::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Division $division)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:divisions,name,'.$division->id,
        ]);

        $division->update($validated);

        return redirect()->back();
    }

    public function destroy(Division $division)
    {
        if ($division->featureRequests()->count() > 0) {
            return redirect()->back()->withErrors(['division' => 'Cannot delete division with existing feature requests.']);
        }

        $division->delete();

        return redirect()->back();
    }
}
