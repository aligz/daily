<?php

namespace App\Http\Controllers;

use App\Models\Routine;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function move(Request $request, Routine $routine)
    {
        Gate::authorize('manage', $routine->user);

        $validated = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ]);

        $routines = $routine->user->routines()
            ->whereNull('archived_on')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $routines->search(fn (Routine $item) => $item->id === $routine->id);

        if ($index === false) {
            abort(404);
        }

        $target = $validated['direction'] === 'up' ? $index - 1 : $index + 1;

        if ($target < 0 || $target >= $routines->count()) {
            return back();
        }

        $ids = $routines->pluck('id')->all();
        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $position => $id) {
                Routine::query()->whereKey($id)->update(['position' => $position + 1]);
            }
        });

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
