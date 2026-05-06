# Task Priority Reordering Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow users to reorder tasks within backlog and todo columns using ↑/↓ buttons, with order persisted to the database per user per column.

**Architecture:** Add a nullable `position` (double) column to `tasks`. Positions are scoped per user+status. A new `PATCH /tasks/{task}/reorder` endpoint accepts a computed midpoint position. The frontend computes the new position from neighbor positions and sends it; the server writes it and rebalances if the gap between adjacent positions drops below 0.001.

**Tech Stack:** Laravel 11, Svelte 5 (runes syntax), Inertia.js, PHP, TypeScript, Tailwind CSS

---

## File Map

| File | Change |
|------|--------|
| `database/migrations/2026_05_06_000000_add_position_to_tasks_table.php` | Create — add `position` double nullable |
| `app/Models/Task.php` | Modify — add `position` to `$fillable` |
| `app/Http/Controllers/TaskController.php` | Modify — update `store`, `update`, `index`; add `reorder` |
| `routes/web.php` | Modify — add `Route::patch` for reorder |
| `resources/js/pages/tasks/Index.svelte` | Modify — add `position` to type, ↑/↓ buttons, `reorderTask` fn |

---

### Task 1: Migration — add position column

**Files:**
- Create: `database/migrations/2026_05_06_000000_add_position_to_tasks_table.php`

- [ ] **Step 1: Create the migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->double('position')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

Expected output: `Running migrations... 2026_05_06_000000_add_position_to_tasks_table ............ DONE`

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_05_06_000000_add_position_to_tasks_table.php
git commit -m "feat: add position column to tasks table"
```

---

### Task 2: Update Task Model

**Files:**
- Modify: `app/Models/Task.php`

- [ ] **Step 1: Add `position` to fillable and casts**

Replace the `$fillable` array and `$casts` array in `app/Models/Task.php`:

```php
protected $fillable = [
    'user_id',
    'title',
    'description',
    'reference',
    'status',
    'completed_at',
    'position',
];

protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'completed_at' => 'datetime',
    'position' => 'float',
];
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/Task.php
git commit -m "feat: add position to Task model fillable and casts"
```

---

### Task 3: Update TaskController — store, update, index, and add reorder

**Files:**
- Modify: `app/Http/Controllers/TaskController.php`

- [ ] **Step 1: Update the `index` method to sort by position for backlog/todo**

Replace the entire `index` method (lines 13–28):

```php
public function index()
{
    $users = \App\Models\User::select('id', 'name', 'email')->get();

    $tasks = Task::with(['user', 'history'])
        ->get()
        ->groupBy('status')
        ->map(function ($statusTasks, $status) {
            if (in_array($status, ['backlog', 'todo'])) {
                return $statusTasks->sortBy('position')->values();
            }
            return $statusTasks->sortByDesc(function ($task) {
                return $task->completed_at ?? $task->created_at;
            })->values();
        });

    return Inertia::render('Tasks/Index', [
        'tasks' => $tasks,
        'users' => $users
    ]);
}
```

- [ ] **Step 2: Update the `store` method to assign initial position**

Replace the entire `store` method (lines 30–54):

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'reference' => 'nullable|string|max:50',
        'status' => 'required|in:backlog,todo,today,done',
    ]);

    DB::transaction(function () use ($validated) {
        if (($validated['status'] ?? null) === 'done') {
            $validated['completed_at'] = now();
        }

        if (in_array($validated['status'], ['backlog', 'todo'])) {
            $max = auth()->user()->tasks()
                ->where('status', $validated['status'])
                ->max('position') ?? 0.0;
            $validated['position'] = $max + 1.0;
        }

        $task = auth()->user()->tasks()->create($validated);

        TaskHistory::create([
            'task_id' => $task->id,
            'from_status' => null,
            'to_status' => $task->status,
        ]);
    });

    return redirect()->back();
}
```

- [ ] **Step 3: Update the `update` method to manage position on status change**

Replace the entire `update` method (lines 56–109):

```php
public function update(Request $request, Task $task)
{
    if ($task->user_id !== auth()->id()) {
        abort(403, 'Unauthorized action.');
    }

    $validated = $request->validate([
        'title' => 'sometimes|string|max:255',
        'description' => 'nullable|string',
        'reference' => 'nullable|string|max:50',
        'status' => 'sometimes|in:backlog,todo,today,done',
    ]);

    DB::transaction(function () use ($task, $validated) {
        $oldStatus = $task->status;
        $newStatus = $validated['status'] ?? $oldStatus;

        if ($newStatus === 'done' && $oldStatus !== 'done') {
            $validated['completed_at'] = now();
        } elseif ($newStatus !== 'done') {
            $validated['completed_at'] = null;
        }

        if ($newStatus === 'today' && $oldStatus !== 'today') {
            $existingTodayTask = auth()->user()->tasks()
                ->where('status', 'today')
                ->where('id', '!=', $task->id)
                ->first();

            if ($existingTodayTask) {
                $maxPosition = auth()->user()->tasks()
                    ->where('status', 'todo')
                    ->max('position') ?? 0.0;
                $existingTodayTask->update([
                    'status' => 'todo',
                    'position' => $maxPosition + 1.0,
                ]);
                TaskHistory::create([
                    'task_id' => $existingTodayTask->id,
                    'from_status' => 'today',
                    'to_status' => 'todo',
                ]);
            }
        }

        if (isset($validated['status']) && $oldStatus !== $newStatus) {
            if (in_array($newStatus, ['today', 'done'])) {
                $validated['position'] = null;
            } elseif (in_array($newStatus, ['backlog', 'todo'])) {
                $max = auth()->user()->tasks()
                    ->where('status', $newStatus)
                    ->max('position') ?? 0.0;
                $validated['position'] = $max + 1.0;
            }
        }

        $task->update($validated);

        if (isset($validated['status']) && $oldStatus !== $newStatus) {
            TaskHistory::create([
                'task_id' => $task->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
            ]);
        }
    });

    return redirect()->back();
}
```

- [ ] **Step 4: Add the `reorder` method at the end of the class (before the closing `}`)**

```php
public function reorder(Request $request, Task $task)
{
    if ($task->user_id !== auth()->id()) {
        abort(403, 'Unauthorized action.');
    }

    if (!in_array($task->status, ['backlog', 'todo'])) {
        abort(422, 'Cannot reorder tasks with this status.');
    }

    $validated = $request->validate([
        'position' => 'required|numeric',
    ]);

    $task->update(['position' => $validated['position']]);

    $tasks = auth()->user()->tasks()
        ->where('status', $task->status)
        ->orderBy('position')
        ->get();

    $needsRebalance = false;
    for ($i = 1; $i < $tasks->count(); $i++) {
        if (($tasks[$i]->position - $tasks[$i - 1]->position) < 0.001) {
            $needsRebalance = true;
            break;
        }
    }

    if ($needsRebalance) {
        foreach ($tasks as $index => $t) {
            $t->update(['position' => (float)($index + 1)]);
        }
    }

    return redirect()->back();
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/TaskController.php
git commit -m "feat: handle position in task store/update/reorder"
```

---

### Task 4: Add reorder route

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Add the PATCH reorder route inside the auth middleware group**

In `routes/web.php`, add this line after the `Route::resource(...)` line:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tasks', \App\Http\Controllers\TaskController::class);
    Route::patch('tasks/{task}/reorder', [\App\Http\Controllers\TaskController::class, 'reorder'])->name('tasks.reorder');
    Route::get('reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
});
```

- [ ] **Step 2: Verify the route is registered**

```bash
php artisan route:list --name=tasks
```

Expected: you see a row for `tasks.reorder` with method `PATCH` and URI `tasks/{task}/reorder`.

- [ ] **Step 3: Commit**

```bash
git add routes/web.php
git commit -m "feat: add PATCH tasks/{task}/reorder route"
```

---

### Task 5: Update frontend — types, reorder logic, and ↑/↓ buttons

**Files:**
- Modify: `resources/js/pages/tasks/Index.svelte`

- [ ] **Step 1: Add `position` to the Task interface**

In the `<script lang="ts">` block, update the `Task` interface (around line 34):

```typescript
interface Task {
    id: number;
    title: string;
    description: string | null;
    reference: string | null;
    status: 'backlog' | 'todo' | 'today' | 'done';
    position: number | null;
    created_at: string;
    completed_at: string | null;
    user: User;
    history: {
        id: number;
        from_status: string | null;
        to_status: string;
        created_at: string;
    }[];
}
```

- [ ] **Step 2: Add the `reorderTask` function**

Add this function in the `<script>` block, after the `moveTask` function (after line 91):

```typescript
function reorderTask(task: Task, direction: 'up' | 'down') {
    const board = task.status as 'backlog' | 'todo';
    const myTasks = (filteredTasks[board] || [])
        .filter((t) => t.user.id === $page.props.auth.user.id)
        .slice()
        .sort((a, b) => (a.position ?? 0) - (b.position ?? 0));

    const idx = myTasks.findIndex((t) => t.id === task.id);
    if (idx === -1) return;

    let newPosition: number;

    if (direction === 'up') {
        if (idx === 0) return;
        const prev = myTasks[idx - 1];
        const prevPrev = myTasks[idx - 2];
        newPosition = prevPrev
            ? (prevPrev.position! + prev.position!) / 2
            : prev.position! / 2;
    } else {
        if (idx === myTasks.length - 1) return;
        const next = myTasks[idx + 1];
        const nextNext = myTasks[idx + 2];
        newPosition = nextNext
            ? (next.position! + nextNext.position!) / 2
            : next.position! + 1.0;
    }

    router.patch(
        `/tasks/${task.id}/reorder`,
        { position: newPosition },
        { preserveScroll: true },
    );
}
```

- [ ] **Step 3: Add ↑/↓ buttons to backlog/todo task cards**

In the template, find the non-done `{#each}` block (around line 607). Replace the task card `<div role="button">` wrapper with a version that includes the reorder buttons. The full replacement for the non-done card wrapper:

```svelte
{#each filteredTasks[board] || [] as task (task.id)}
    {@const myBoardTasks = (filteredTasks[board] || [])
        .filter((t) => t.user.id === $page.props.auth.user.id)
        .slice()
        .sort((a, b) => (a.position ?? 0) - (b.position ?? 0))}
    {@const myIdx = myBoardTasks.findIndex((t) => t.id === task.id)}
    <div class="flex items-start gap-1 group">
        {#if task.user.id === $page.props.auth.user.id && (board === 'backlog' || board === 'todo')}
            <div class="flex flex-col gap-0.5 pt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <button
                    type="button"
                    disabled={myIdx === 0}
                    onclick={(e) => { e.stopPropagation(); reorderTask(task, 'up'); }}
                    class="flex h-5 w-5 items-center justify-center rounded text-xs text-muted-foreground hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-30"
                    aria-label="Move up"
                >
                    ↑
                </button>
                <button
                    type="button"
                    disabled={myIdx === myBoardTasks.length - 1}
                    onclick={(e) => { e.stopPropagation(); reorderTask(task, 'down'); }}
                    class="flex h-5 w-5 items-center justify-center rounded text-xs text-muted-foreground hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-30"
                    aria-label="Move down"
                >
                    ↓
                </button>
            </div>
        {:else}
            <div class="w-6 shrink-0"></div>
        {/if}
        <div
            role="button"
            tabindex="0"
            class="flex-1 {task.user.id === $page.props.auth.user.id
                ? 'cursor-grab active:cursor-grabbing'
                : 'cursor-pointer'}"
            draggable={task.user.id === $page.props.auth.user.id}
            ondragstart={(e) => handleDragStart(e, task)}
            onclick={() => openDialog(task)}
            onkeydown={(e) => e.key === 'Enter' && openDialog(task)}
        >
            <Card
                class="shadow-none hover:shadow-md transition-shadow border-transparent py-2 gap-0"
            >
                <CardHeader class="p-3 pb-1 space-y-0">
                    <div class="flex items-start justify-between gap-2">
                        <CardTitle class="text-sm font-medium leading-snug">
                            {task.title}
                        </CardTitle>
                        <Avatar class="h-5 w-5">
                            <AvatarFallback
                                class="text-[9px] {getUserColor(task.user.name)}"
                            >
                                {getInitials(task.user.name)}
                            </AvatarFallback>
                        </Avatar>
                    </div>
                    {#if task.reference}
                        <CardDescription class="text-[10px] font-mono">
                            {task.reference}
                        </CardDescription>
                    {/if}
                </CardHeader>
                {#if task.description}
                    <CardContent class="p-3 pt-1">
                        <p class="text-[11px] text-muted-foreground line-clamp-2">
                            {task.description}
                        </p>
                    </CardContent>
                {/if}
            </Card>
        </div>
    </div>
{/each}
```

- [ ] **Step 4: Build and verify no TypeScript errors**

```bash
npm run build
```

Expected: build completes with no errors.

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/tasks/Index.svelte
git commit -m "feat: add priority reorder arrows to backlog and todo task cards"
```

---

### Task 6: Manual smoke test

- [ ] **Step 1: Start the dev server**

```bash
php artisan serve
```

In a separate terminal:
```bash
npm run dev
```

- [ ] **Step 2: Verify backlog/todo reorder**

1. Open the Tasks page in the browser.
2. Create 3 tasks in Backlog.
3. Hover over the second task — ↑ and ↓ arrows should appear on the left.
4. Click ↑ — the task should move above the first task. Refresh the page; order should persist.
5. Click ↓ on the same task — it should return to its original position.
6. Verify the top task has ↑ disabled (grayed out) and the bottom task has ↓ disabled.

- [ ] **Step 3: Verify today/done columns are unaffected**

Confirm no ↑/↓ buttons appear on Today or Done task cards.

- [ ] **Step 4: Verify status change resets position**

Drag a task from Backlog to Today. Then drag it back to Backlog. It should appear at the bottom (highest position).
