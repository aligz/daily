<?php

namespace App\Http\Controllers;

use App\Models\Routine;
use App\Models\RoutineEntry;
use App\Models\RoutineNote;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ChecklistController extends Controller
{
    public function check(Request $request, Routine $routine)
    {
        Gate::authorize('manage', $routine->user);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(['done', 'skipped', 'pending'])],
        ]);

        $timezone = config('tasks.display_timezone');
        $today = CarbonImmutable::now($timezone)->toDateString();
        $yesterday = CarbonImmutable::parse($today, $timezone)->subDay()->toDateString();

        abort_unless(
            in_array($validated['date'], [$today, $yesterday], true),
            422,
            'You can only check routines for today or yesterday.',
        );

        abort_unless(
            $routine->isActiveOn($validated['date']),
            422,
            'This routine is not active on that date.',
        );

        if ($validated['status'] === 'pending') {
            RoutineEntry::query()
                ->where('routine_id', $routine->id)
                ->whereDate('check_date', $validated['date'])
                ->delete();
        } else {
            $routine->entries()->updateOrCreate(
                ['check_date' => $validated['date']],
                ['status' => $validated['status'], 'checked_at' => now()],
            );
        }

        return back();
    }

    public function saveNote(Request $request)
    {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:'.config('checklist.note_max_length')],
        ]);

        $body = trim((string) ($validated['body'] ?? ''));
        $today = CarbonImmutable::now(config('tasks.display_timezone'))->toDateString();

        if ($body === '') {
            RoutineNote::query()
                ->where('user_id', auth()->id())
                ->whereDate('note_date', $today)
                ->delete();
        } else {
            RoutineNote::query()->updateOrCreate(
                ['user_id' => auth()->id(), 'note_date' => $today],
                ['body' => $body],
            );
        }

        return back();
    }
}
