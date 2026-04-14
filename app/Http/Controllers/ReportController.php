<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $dateRange = $request->get('date_range', '30'); // days
        $startDate = now()->subDays($dateRange);

        $users = User::with(['tasks' => function ($query) use ($startDate) {
            $query->where('created_at', '>=', $startDate)
                ->orWhereHas('history', function ($q) use ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                });
        }])->get();

        $userReports = $users->map(function ($user) use ($startDate) {
            $tasks = $user->tasks()->where('created_at', '>=', $startDate)->get();
            
            // Get tasks that were completed in the date range
            $completedTasks = $user->tasks()
                ->where('status', 'done')
                ->where('completed_at', '>=', $startDate)
                ->get();

            // Calculate average completion time for completed tasks
            $completionTimes = [];
            foreach ($completedTasks as $task) {
                // Find when the task was first moved to 'today'
                $movedToToday = $task->history()
                    ->where('to_status', 'today')
                    ->where('created_at', '>=', $startDate)
                    ->orderBy('created_at', 'asc')
                    ->first();

                if ($movedToToday && $task->completed_at) {
                    $hours = $movedToToday->created_at->diffInHours($task->completed_at);
                    $completionTimes[] = $hours;
                } elseif ($task->completed_at) {
                    // If no 'today' history, use created_at as start
                    $hours = $task->created_at->diffInHours($task->completed_at);
                    $completionTimes[] = $hours;
                }
            }

            $avgCompletionTime = count($completionTimes) > 0 
                ? round(array_sum($completionTimes) / count($completionTimes), 2) 
                : null;

            // Tasks by status
            $statusBreakdown = [
                'backlog' => $tasks->where('status', 'backlog')->count(),
                'todo' => $tasks->where('status', 'todo')->count(),
                'today' => $tasks->where('status', 'today')->count(),
                'done' => $completedTasks->count(),
            ];

            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
                'total_tasks' => $tasks->count(),
                'completed_tasks' => $completedTasks->count(),
                'avg_completion_time_hours' => $avgCompletionTime,
                'status_breakdown' => $statusBreakdown,
                'completion_times' => $completionTimes,
            ];
        });

        // Sort by completed tasks (descending)
        $userReports = $userReports->sortByDesc('completed_tasks')->values();

        return Inertia::render('Report/Index', [
            'userReports' => $userReports,
            'dateRange' => (int) $dateRange,
        ]);
    }
}
