<script lang="ts">
    import { Link, useForm, router, page } from '@inertiajs/svelte';
    import { Plus } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import TaskCard from '@/components/tasks/TaskCard.svelte';
    import TaskHeader from '@/components/tasks/TaskHeader.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogHeader,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { taskBoardHeaderStyles } from '@/lib/board-colors';
    import { todayInAppTimezone } from '@/lib/date';
    import { store, update, destroy } from '@/routes/tasks';
    import type {
        BoardTask as Task,
        BoardUser as User,
        TaskMetrics,
    } from '@/types/tasks';

    let {
        tasks,
        users,
        metrics,
        routine,
    }: {
        tasks: Record<string, Task[]>;
        users: User[];
        metrics: TaskMetrics;
        routine: { done: number; skipped: number; total: number };
    } = $props();

    const boards = ['backlog', 'todo', 'today', 'done'];
    const today = todayInAppTimezone();

    // Filter State
    let selectedUserId = $state<number | null>(null);

    // Filtered Tasks
    let filteredTasks = $derived.by(() => {
        if (!selectedUserId) {
            return tasks;
        }
        const filtered: Record<string, Task[]> = {};
        for (const board of boards) {
            filtered[board] = (tasks[board] || []).filter(
                (task) => task.user.id === selectedUserId,
            );
        }
        return filtered;
    });

    const myTasksByBoard = $derived.by(() => {
        const currentUserId = $page.props.auth.user.id;
        const result: Record<string, Task[]> = {};

        for (const board of ['backlog', 'todo']) {
            result[board] = (filteredTasks[board] ?? [])
                .filter((task) => task.user.id === currentUserId)
                .slice()
                .sort(
                    (first, second) =>
                        (first.position ?? 0) - (second.position ?? 0),
                );
        }

        return result;
    });

    // Move Task Logic
    function moveTask(task: Task, newStatus: string) {
        if (task.status === newStatus) return;
        router.put(
            update.url({ task: task.id }),
            {
                status: newStatus,
            },
            {
                preserveScroll: true,
            },
        );
    }

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

    // Drag and Drop Logic
    let draggedTask = $state<Task | null>(null);

    function handleDragStart(event: DragEvent, task: Task) {
        if (task.user.id !== $page.props.auth.user.id) return;
        draggedTask = task;
        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.dropEffect = 'move';
            event.dataTransfer.setData('text/plain', JSON.stringify(task));
        }
    }

    function handleDrop(event: DragEvent, status: string) {
        event.preventDefault();
        if (draggedTask) {
            moveTask(draggedTask, status);
            draggedTask = null;
        }
    }

    function handleDragOver(event: DragEvent) {
        event.preventDefault();
        if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
    }

    function groupTasksByDate(tasks: Task[]) {
        const groups: Record<string, Task[]> = {};
        tasks
            .slice()
            .sort((a, b) => {
                const timeA = a.completed_at
                    ? new Date(a.completed_at).getTime()
                    : 0;
                const timeB = b.completed_at
                    ? new Date(b.completed_at).getTime()
                    : 0;
                return timeB - timeA;
            })
            .forEach((task) => {
                let date = 'Unknown Date';
                if (task.completed_at) {
                    date = new Date(task.completed_at).toLocaleDateString(
                        'id-ID',
                        {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                        },
                    );
                }
                if (!groups[date]) {
                    groups[date] = [];
                }
                groups[date].push(task);
            });
        return groups;
    }

    // Create/Edit Task Logic
    let isOpen = $state(false);
    let editingTask = $state<Task | null>(null);

    const form = useForm({
        title: '',
        description: '',
        reference: '',
        planned_on: '',
        status: 'backlog' as Task['status'],
    });

    function openDialog(task?: Task) {
        if (task) {
            editingTask = task;
            $form.title = task.title;
            $form.description = task.description || '';
            $form.reference = task.reference || '';
            $form.planned_on = task.planned_on ?? '';
            $form.status = task.status;
        } else {
            editingTask = null;
            $form.reset();
        }
        isOpen = true;
    }

    function submit() {
        if (editingTask) {
            $form.put(update.url({ task: editingTask.id }), {
                onSuccess: () => {
                    isOpen = false;
                    editingTask = null;
                    $form.reset();
                },
            });
        } else {
            $form.post(store.url(), {
                onSuccess: () => {
                    isOpen = false;
                    $form.reset();
                },
            });
        }
    }

    function deleteTask() {
        if (
            editingTask &&
            confirm('Are you sure you want to delete this task?')
        ) {
            router.delete(destroy.url({ task: editingTask.id }), {
                onSuccess: () => {
                    isOpen = false;
                    editingTask = null;
                    $form.reset();
                },
            });
        }
    }

    let isOwner = $derived.by(() =>
        editingTask ? editingTask.user.id === $page.props.auth.user.id : true,
    );
</script>

<AppHead title="Tasks" />

<AppLayout>
    <div class="flex h-full flex-col p-4">
        <TaskHeader
            active="board"
            eyebrow="Tasks / Board"
            title="Task Board"
            subtitle="Drag cards between columns. Today's routine lives in the calendar."
            {users}
            {selectedUserId}
            onUserChange={(id) => (selectedUserId = id)}
        >
            {#snippet badge()}
                {#if routine.total > 0}
                    <Link
                        href="/tasks/calendar"
                        class="inline-flex items-center gap-1.5 rounded-sm border border-routine/40 bg-routine/10 px-2 py-1 text-xs text-routine transition-colors hover:bg-routine/15"
                        title="Open the calendar to check your routine"
                    >
                        <span aria-hidden="true">▣</span>
                        {routine.done + routine.skipped}/{routine.total} routine
                    </Link>
                {/if}
            {/snippet}

            {#snippet actions()}
                <Button size="sm" onclick={() => openDialog()}>
                    <Plus class="mr-1.5 h-3.5 w-3.5" />
                    New Task
                </Button>
            {/snippet}
        </TaskHeader>

        <Dialog bind:open={isOpen}>
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle
                        >{editingTask
                            ? 'Edit Task'
                            : 'Create Task'}</DialogTitle
                    >
                    <DialogDescription>
                        {editingTask
                            ? 'Edit the details of your task.'
                            : 'Add a new task to your board.'} Click save when you're
                        done.
                    </DialogDescription>
                </DialogHeader>
                <form
                    onsubmit={(e) => {
                        e.preventDefault();
                        submit();
                    }}
                    class="grid gap-4 py-4"
                >
                    <div class="grid gap-2">
                        <Label for="title">Title</Label>
                        <Input
                            id="title"
                            bind:value={$form.title}
                            placeholder="Task title"
                            autocomplete="off"
                            disabled={!isOwner}
                        />
                        {#if $form.errors.title}
                            <span class="text-sm text-destructive"
                                >{$form.errors.title}</span
                            >
                        {/if}
                    </div>
                    <div class="grid gap-2">
                        <Label for="reference">Reference (Optional)</Label>
                        <Input
                            id="reference"
                            bind:value={$form.reference}
                            placeholder="e.g. JIRA-123"
                            autocomplete="off"
                            disabled={!isOwner}
                        />
                        {#if $form.errors.reference}
                            <span class="text-sm text-destructive"
                                >{$form.errors.reference}</span
                            >
                        {/if}
                    </div>
                    <div class="grid gap-2">
                        <Label for="planned_on">Planned for (Optional)</Label>
                        <Input
                            id="planned_on"
                            type="date"
                            min={today}
                            bind:value={$form.planned_on}
                            disabled={!isOwner}
                        />
                        {#if $form.errors.planned_on}
                            <span class="text-sm text-destructive"
                                >{$form.errors.planned_on}</span
                            >
                        {/if}
                    </div>
                    <div class="grid gap-2">
                        <Label for="description">Description (Optional)</Label>
                        <textarea
                            id="description"
                            bind:value={$form.description}
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Task details..."
                            disabled={!isOwner}
                        ></textarea>
                        {#if $form.errors.description}
                            <span class="text-sm text-destructive"
                                >{$form.errors.description}</span
                            >
                        {/if}
                    </div>

                    {#if editingTask && editingTask.history && editingTask.history.length > 0}
                        <div class="grid gap-2">
                            <Label>History</Label>
                            <div
                                class="rounded-md border p-2 text-xs text-muted-foreground bg-muted/50 max-h-[150px] overflow-y-auto"
                            >
                                {#each editingTask.history
                                    .slice()
                                    .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()) as history (history.id)}
                                    <div class="mb-1 last:mb-0">
                                        <span class="font-mono"
                                            >{new Date(
                                                history.created_at,
                                            ).toLocaleString('id-ID', {
                                                day: 'numeric',
                                                month: 'short',
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            })}</span
                                        >:
                                        {#if history.from_status}
                                            Changed from <span
                                                class="font-semibold capitalize"
                                                >{history.from_status}</span
                                            >
                                            to
                                            <span
                                                class="font-semibold capitalize"
                                                >{history.to_status}</span
                                            >
                                        {:else}
                                            Created in <span
                                                class="font-semibold capitalize"
                                                >{history.to_status}</span
                                            >
                                        {/if}
                                    </div>
                                {/each}
                            </div>
                        </div>
                    {/if}

                    <DialogFooter class="sm:justify-between w-full mt-4">
                        {#if editingTask && isOwner}
                            <Button
                                type="button"
                                variant="destructive"
                                onclick={deleteTask}
                                disabled={$form.processing}>Delete</Button
                            >
                        {/if}
                        {#if isOwner}
                            <Button type="submit" disabled={$form.processing}
                                >{editingTask
                                    ? 'Update Task'
                                    : 'Save Task'}</Button
                            >
                        {/if}
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <div class="flex flex-1 min-h-0 gap-6 overflow-x-auto pb-4">
            {#each boards as board (board)}
                <div
                    class="flex min-h-0 flex-1 min-w-[300px] max-w-[350px] flex-col rounded-md border border-muted/50 p-3 bg-muted/50"
                    role="region"
                    aria-label="{board} column"
                    ondrop={(e) => handleDrop(e, board)}
                    ondragover={handleDragOver}
                >
                    <div
                        class="mb-3 flex items-center justify-between rounded-md border px-3 py-2
                        {taskBoardHeaderStyles[board] ?? 'bg-muted/50'}"
                    >
                        <h3 class="font-semibold capitalize">
                            {board}
                        </h3>
                        <span
                            class="rounded-full bg-background px-2 py-0.5 text-xs font-medium text-muted-foreground border"
                        >
                            {filteredTasks[board]?.length || 0}
                        </span>
                    </div>

                    <div
                        class="flex flex-1 flex-col gap-3 overflow-y-auto pr-1"
                    >
                        {#if board === 'done'}
                            {#each Object.entries(groupTasksByDate(filteredTasks[board] || [])) as [date, groupTasks] (date)}
                                <div
                                    class="text-xs font-semibold text-muted-foreground mt-2 mb-1"
                                >
                                    {date}
                                </div>
                                {#each groupTasks as task (task.id)}
                                    <TaskCard
                                        {task}
                                        mine={task.user.id ===
                                            $page.props.auth.user.id}
                                        metric={metrics[task.id]}
                                        onOpen={openDialog}
                                        onDragStart={handleDragStart}
                                        onMove={(status) =>
                                            moveTask(task, status)}
                                    />
                                {/each}
                            {/each}
                        {:else}
                            {@const myBoardTasks = myTasksByBoard[board] ?? []}
                            {#each filteredTasks[board] || [] as task (task.id)}
                                {@const myIndex = myBoardTasks.findIndex(
                                    (item) => item.id === task.id,
                                )}
                                <TaskCard
                                    {task}
                                    mine={task.user.id ===
                                        $page.props.auth.user.id}
                                    metric={metrics[task.id]}
                                    canMoveUp={myIndex > 0}
                                    canMoveDown={myIndex !== -1 &&
                                        myIndex < myBoardTasks.length - 1}
                                    onOpen={openDialog}
                                    onDragStart={handleDragStart}
                                    onMove={(status) => moveTask(task, status)}
                                    onMoveUp={() => reorderTask(task, 'up')}
                                    onMoveDown={() => reorderTask(task, 'down')}
                                />
                            {/each}
                        {/if}

                        {#if (filteredTasks[board]?.length || 0) === 0}
                            <div
                                class="flex h-24 flex-col items-center justify-center rounded-lg border border-dashed text-sm text-muted-foreground"
                            >
                                No tasks
                            </div>
                        {/if}
                    </div>
                </div>
            {/each}
        </div>
    </div>
</AppLayout>
