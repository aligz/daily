<script lang="ts">
    import AppLayout from '@/layouts/AppLayout.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { useForm, router } from '@inertiajs/svelte';
    import type { BreadcrumbItem } from '@/types';
    import { Plus, GripVertical } from 'lucide-svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { store, update } from '@/routes/tasks';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogHeader,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';

    interface User {
        id: number;
        name: string;
        email: string;
    }

    interface Task {
        id: number;
        title: string;
        description: string | null;
        reference: string | null;
        status: 'backlog' | 'todo' | 'today' | 'done';
        created_at: string;
        completed_at: string | null;
        user: User;
    }

    export let tasks: Record<string, Task[]>;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tasks',
            href: '/tasks',
        },
    ];

    const boards = ['backlog', 'todo', 'today', 'done'];

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

    // Drag and Drop Logic
    let draggedTask: Task | null = null;

    function handleDragStart(event: DragEvent, task: Task) {
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

    function getInitials(name: string) {
        return name
            .split(' ')
            .map((n) => n[0])
            .slice(0, 2)
            .join('')
            .toUpperCase();
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
    let isOpen = false;
    let editingTask: Task | null = null;

    const form = useForm({
        title: '',
        description: '',
        reference: '',
        status: 'backlog' as Task['status'],
    });

    function openDialog(task?: Task) {
        if (task) {
            editingTask = task;
            $form.title = task.title;
            $form.description = task.description || '';
            $form.reference = task.reference || '';
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
                    $form.reset();
                    editingTask = null;
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

    // Watch for dialog close to reset state
    $: if (!isOpen) {
        // slight delay to check if it wasn't just a re-render or if we need this
        // actually better to just reset when opening new one or explicitly closing
        if (!editingTask) $form.reset();
    }
</script>

<AppHead title="Tasks" />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Task Board</h1>
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
                                : 'Add a new task to your board.'} Click save when
                            you're done.
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        on:submit|preventDefault={submit}
                        class="grid gap-4 py-4"
                    >
                        <div class="grid gap-2">
                            <Label for="title">Title</Label>
                            <Input
                                id="title"
                                bind:value={$form.title}
                                placeholder="Task title"
                                autocomplete="off"
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
                            />
                            {#if $form.errors.reference}
                                <span class="text-sm text-destructive"
                                    >{$form.errors.reference}</span
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
                            ></textarea>
                            {#if $form.errors.description}
                                <span class="text-sm text-destructive"
                                    >{$form.errors.description}</span
                                >
                            {/if}
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={$form.processing}
                                >{editingTask
                                    ? 'Update Task'
                                    : 'Save Task'}</Button
                            >
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <div class="flex h-full gap-6 overflow-x-auto pb-4">
            {#each boards as board}
                <div
                    class="flex h-full min-w-[300px] max-w-[350px] flex-col rounded-xl border p-4 {board ===
                    'done'
                        ? 'bg-green-100/50 border-green-200'
                        : board === 'today'
                          ? 'bg-blue-100/50 border-blue-200'
                          : board === 'todo'
                            ? 'bg-orange-100/50 border-orange-200'
                            : 'bg-muted/50'}"
                    role="region"
                    aria-label="{board} column"
                    on:drop={(e) => handleDrop(e, board)}
                    on:dragover={handleDragOver}
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-semibold capitalize text-foreground">
                            {board}
                        </h3>
                        <span
                            class="rounded-full bg-background px-2 py-0.5 text-xs font-medium text-muted-foreground border"
                        >
                            {tasks[board]?.length || 0}
                        </span>
                    </div>

                    <div
                        class="flex flex-1 flex-col gap-3 overflow-y-auto pr-1"
                    >
                        {#if board === 'done'}
                            {#each Object.entries(groupTasksByDate(tasks[board] || [])) as [date, groupTasks]}
                                <div
                                    class="text-xs font-semibold text-muted-foreground mt-2 mb-1"
                                >
                                    {date}
                                </div>
                                {#each groupTasks as task (task.id)}
                                    <!-- Task Card Component -->
                                    <div
                                        role="button"
                                        tabindex="0"
                                        class="cursor-grab active:cursor-grabbing"
                                        draggable="true"
                                        on:dragstart={(e) =>
                                            handleDragStart(e, task)}
                                        on:click={() => openDialog(task)}
                                        on:keydown={(e) =>
                                            e.key === 'Enter' &&
                                            openDialog(task)}
                                    >
                                        <Card
                                            class="shadow-none hover:shadow-md transition-shadow py-2 gap-2"
                                        >
                                            <CardHeader
                                                class="p-3 pb-1 space-y-0"
                                            >
                                                <div
                                                    class="flex items-start justify-between gap-2"
                                                >
                                                    <CardTitle
                                                        class="text-sm font-medium leading-snug"
                                                    >
                                                        {task.title}
                                                    </CardTitle>
                                                    <Avatar class="h-5 w-5">
                                                        <AvatarFallback
                                                            class="text-[9px]"
                                                        >
                                                            {getInitials(
                                                                task.user.name,
                                                            )}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                </div>
                                                {#if task.reference}
                                                    <CardDescription
                                                        class="text-[10px] font-mono"
                                                    >
                                                        {task.reference}
                                                    </CardDescription>
                                                {/if}
                                            </CardHeader>
                                            {#if task.description}
                                                <CardContent class="p-3 pt-1">
                                                    <p
                                                        class="text-[11px] text-muted-foreground line-clamp-2"
                                                    >
                                                        {task.description}
                                                    </p>
                                                </CardContent>
                                            {/if}
                                        </Card>
                                    </div>
                                {/each}
                            {/each}
                        {:else}
                            {#each tasks[board] || [] as task (task.id)}
                                <div
                                    role="button"
                                    tabindex="0"
                                    class="cursor-grab active:cursor-grabbing"
                                    draggable="true"
                                    on:dragstart={(e) =>
                                        handleDragStart(e, task)}
                                    on:click={() => openDialog(task)}
                                    on:keydown={(e) =>
                                        e.key === 'Enter' && openDialog(task)}
                                >
                                    <Card
                                        class="shadow-none hover:shadow-md transition-shadow py-2 gap-0"
                                    >
                                        <CardHeader class="p-3 pb-1 space-y-0">
                                            <div
                                                class="flex items-start justify-between gap-2"
                                            >
                                                <CardTitle
                                                    class="text-sm font-medium leading-snug"
                                                >
                                                    {task.title}
                                                </CardTitle>
                                                <Avatar class="h-5 w-5">
                                                    <AvatarFallback
                                                        class="text-[9px]"
                                                    >
                                                        {getInitials(
                                                            task.user.name,
                                                        )}
                                                    </AvatarFallback>
                                                </Avatar>
                                            </div>
                                            {#if task.reference}
                                                <CardDescription
                                                    class="text-[10px] font-mono"
                                                >
                                                    {task.reference}
                                                </CardDescription>
                                            {/if}
                                        </CardHeader>
                                        {#if task.description}
                                            <CardContent class="p-3 pt-1">
                                                <p
                                                    class="text-[11px] text-muted-foreground line-clamp-2"
                                                >
                                                    {task.description}
                                                </p>
                                            </CardContent>
                                        {/if}
                                    </Card>
                                </div>
                            {/each}
                        {/if}

                        {#if (tasks[board]?.length || 0) === 0}
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
