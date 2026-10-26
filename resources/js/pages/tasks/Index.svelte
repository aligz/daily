<script lang="ts">
    import { Link, useForm, router, page } from '@inertiajs/svelte';
    import { Plus } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogHeader,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import TaskCard from '@/components/tasks/TaskCard.svelte';
    import TaskViewToggle from '@/components/tasks/TaskViewToggle.svelte';
    import { clickOutside } from '@/lib/clickOutside';
    import { todayInAppTimezone } from '@/lib/date';
    import { getInitials } from '@/lib/initials';
    import { getUserColor } from '@/lib/users';
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
    let showUserDropdown = $state(false);

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

    function getSelectedUser(): User | null {
        if (!selectedUserId) return null;
        return users.find((u) => u.id === selectedUserId) || null;
    }

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

<svelte:window
    onkeydown={(event) => {
        if (event.key === 'Escape') showUserDropdown = false;
    }}
/>

<AppLayout>
    <div class="flex h-full flex-col p-4">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight">Task Board</h1>
                <TaskViewToggle active="board" />

                {#if routine.total > 0}
                    <Link
                        href="/tasks/calendar"
                        class="inline-flex items-center gap-1.5 rounded-sm border border-violet-500/40 bg-violet-500/10 px-2 py-1 text-[11px] text-violet-600 transition-colors hover:bg-violet-500/15 dark:text-violet-300"
                        title="Open the calendar to check your routine"
                    >
                        <span aria-hidden="true">▣</span>
                        {routine.done + routine.skipped}/{routine.total} routine
                    </Link>
                {/if}
            </div>

            <div class="flex gap-4">
                <!-- User Filter Dropdown -->
                <div class="mb-4 flex items-center gap-2">
                    <div class="relative">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                            data-user-filter-trigger
                            aria-haspopup="menu"
                            aria-expanded={showUserDropdown}
                            onclick={() =>
                                (showUserDropdown = !showUserDropdown)}
                        >
                            {#if selectedUserId}
                                {#if getSelectedUser()}
                                    <Avatar class="h-5 w-5">
                                        <AvatarFallback
                                            class="text-[9px] {getUserColor(
                                                getSelectedUser()!.name,
                                            )}"
                                        >
                                            {getInitials(
                                                getSelectedUser()!.name,
                                            )}
                                        </AvatarFallback>
                                    </Avatar>
                                {/if}
                                <span class="max-w-[120px] truncate">
                                    {getSelectedUser()?.name}
                                </span>
                            {:else}
                                <span>All Users</span>
                            {/if}
                            <svg
                                class="h-4 w-4 opacity-50"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        {#if showUserDropdown}
                            <div
                                class="absolute left-0 z-50 mt-2 min-w-[200px] overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
                                data-user-filter-menu
                                use:clickOutside={{
                                    handler: () =>
                                        (showUserDropdown = false),
                                    ignore: '[data-user-filter-trigger]',
                                }}
                            >
                                <button
                                    type="button"
                                    class="relative flex w-full cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
                                    onclick={() => {
                                        selectedUserId = null;
                                        showUserDropdown = false;
                                    }}
                                >
                                    <span class="text-muted-foreground">All Users</span>
                                </button>
                                <div class="h-px bg-border"></div>
                                {#each users as user}
                                    <button
                                        type="button"
                                        class="relative flex w-full cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground {selectedUserId ===
                                        user.id
                                            ? 'bg-accent'
                                            : ''}"
                                        onclick={() => {
                                            selectedUserId = user.id;
                                            showUserDropdown = false;
                                        }}
                                    >
                                        <Avatar class="h-5 w-5">
                                            <AvatarFallback
                                                class="text-[9px] {getUserColor(
                                                    user.name,
                                                )}"
                                            >
                                                {getInitials(user.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <span>{user.name}</span>
                                    </button>
                                {/each}
                            </div>
                        {/if}
                    </div>
                </div>
                <Dialog bind:open={isOpen}>
                    <DialogTrigger asChild>
                        {#snippet children(params)}
                            <Button {...params} onclick={() => openDialog()}>
                                <Plus class="mr-2 h-4 w-4" />
                                New Task
                            </Button>
                        {/snippet}
                    </DialogTrigger>
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
                                    : 'Add a new task to your board.'} Click save
                                when you're done.
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
                                <Label for="reference"
                                    >Reference (Optional)</Label
                                >
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
                                <Label for="planned_on"
                                    >Planned for (Optional)</Label
                                >
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
                                <Label for="description"
                                    >Description (Optional)</Label
                                >
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
                                            .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()) as history}
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

                            <DialogFooter
                                class="sm:justify-between w-full mt-4"
                            >
                                {#if editingTask && isOwner}
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        onclick={deleteTask}
                                        disabled={$form.processing}
                                        >Delete</Button
                                    >
                                {/if}
                                {#if isOwner}
                                    <Button
                                        type="submit"
                                        disabled={$form.processing}
                                        >{editingTask
                                            ? 'Update Task'
                                            : 'Save Task'}</Button
                                    >
                                {/if}
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>

        <div class="flex flex-1 min-h-0 gap-6 overflow-x-auto pb-4">
            {#each boards as board}
                <div
                    class="flex min-h-0 flex-1 min-w-[300px] max-w-[350px] flex-col rounded border border-muted/50 p-4 bg-muted/50"
                    role="region"
                    aria-label="{board} column"
                    ondrop={(e) => handleDrop(e, board)}
                    ondragover={handleDragOver}
                >
                    <div
                        class="mb-4 flex items-center justify-between px-4 py-2 rounded border
                        {board === 'done'
                            ? 'bg-neon-green/5  border-neon-green/20 text-neon-green'
                            : board === 'today'
                              ? 'bg-blue-100/50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800 text-blue-800'
                              : board === 'todo'
                                ? 'bg-orange-100/50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-800 text-orange-800'
                                : 'bg-muted/50'}
                        "
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
                            {#each Object.entries(groupTasksByDate(filteredTasks[board] || [])) as [date, groupTasks]}
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
                                    />
                                {/each}
                            {/each}
                        {:else}
                            {@const myBoardTasks =
                                myTasksByBoard[board] ?? []}
                            {#each filteredTasks[board] || [] as task (task.id)}
                                {@const myIndex = myBoardTasks.findIndex(
                                    (item) => item.id === task.id,
                                )}
                                <TaskCard
                                    {task}
                                    mine={task.user.id ===
                                        $page.props.auth.user.id}
                                    metric={metrics[task.id]}
                                    showReorder={board === 'backlog' ||
                                        board === 'todo'}
                                    canMoveUp={myIndex > 0}
                                    canMoveDown={myIndex !== -1 &&
                                        myIndex < myBoardTasks.length - 1}
                                    onOpen={openDialog}
                                    onDragStart={handleDragStart}
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
