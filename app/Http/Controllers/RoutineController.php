<?php

namespace App\Http\Controllers;

use App\Models\Routine;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RoutineController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $user = auth()->user();

        if ($user->routines()->whereNull('archived_on')->count() >= config('checklist.max_routines')) {
            return back()->withErrors([
                'title' => 'You already have the maximum number of routines.',
            ]);
        }

        $position = (float) ($user->routines()->max('position') ?? 0);

        $user->routines()->create([
            'title' => trim($validated['title']),
            'position' => $position + 1,
            'starts_on' => CarbonImmutable::now(config('tasks.display_timezone'))->toDateString(),
        ]);

        return back();
    }

    public function update(Request $request, Routine $routine)
    {
        Gate::authorize('manage', $routine->user);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $routine->update(['title' => trim($validated['title'])]);

        return back();
    }

    public function archive(Routine $routine)
    {
        Gate::authorize('manage', $routine->user);

        if ($routine->archived_on !== null) {
            abort(404);
        }

        $routine->update([
            'archived_on' => CarbonImmutable::now(config('tasks.display_timezone'))->toDateString(),
        ]);

        return back();
    }
}
