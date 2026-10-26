<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetTodayTasks extends Command
{
    protected $signature = 'tasks:reset-today';

    protected $description = 'Move every task on the today board back to todo';

    public function handle(): int
    {
        $count = DB::transaction(function (): int {
            $tasks = Task::query()->where('status', 'today')->get();

            foreach ($tasks as $task) {
                $maxPosition = (float) (Task::query()
                    ->where('user_id', $task->user_id)
                    ->where('status', 'todo')
                    ->max('position') ?? 0.0);

                $task->update([
                    'status' => 'todo',
                    'position' => $maxPosition + 1.0,
                ]);

                TaskHistory::create([
                    'task_id' => $task->id,
                    'from_status' => 'today',
                    'to_status' => 'todo',
                ]);
            }

            return $tasks->count();
        });

        $this->info("Moved {$count} today task(s) back to todo.");

        return self::SUCCESS;
    }
}
