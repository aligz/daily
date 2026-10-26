<script lang="ts">
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import { getInitials } from '@/lib/initials';
    import { cn } from '@/lib/utils';
    import { getUserColor } from '@/lib/users';
    import type { BoardTask, TaskMetric } from '@/types/tasks';
    import TaskBadges from './TaskBadges.svelte';

    let {
        task,
        mine,
        metric,
        showReorder = false,
        canMoveUp = false,
        canMoveDown = false,
        onOpen,
        onDragStart,
        onMoveUp,
        onMoveDown,
    }: {
        task: BoardTask;
        mine: boolean;
        metric?: TaskMetric;
        showReorder?: boolean;
        canMoveUp?: boolean;
        canMoveDown?: boolean;
        onOpen: (task: BoardTask) => void;
        onDragStart: (event: DragEvent, task: BoardTask) => void;
        onMoveUp?: () => void;
        onMoveDown?: () => void;
    } = $props();

    function handleKeydown(event: KeyboardEvent) {
        if (event.key === 'Enter') {
            onOpen(task);
        }
    }
</script>

<div
    role="button"
    tabindex="0"
    class={cn(
        'group relative',
        mine ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer',
    )}
    draggable={mine}
    ondragstart={(event) => onDragStart(event, task)}
    onclick={() => onOpen(task)}
    onkeydown={handleKeydown}
>
    <Card
        class="shadow-none hover:shadow-md transition-shadow border-transparent py-2 gap-0"
    >
        <CardHeader class="p-3 pb-1 space-y-0">
            <div class="flex items-start justify-between gap-2">
                <CardTitle class="text-sm font-medium leading-snug">
                    {task.title}
                </CardTitle>
                <Avatar class="h-5 w-5 shrink-0">
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

        <TaskBadges {task} {metric} />
    </Card>

    {#if showReorder && mine}
        <div
            class="absolute right-1 top-1/2 -translate-y-1/2 flex flex-col gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity z-10"
        >
            <button
                type="button"
                disabled={!canMoveUp}
                onclick={(event) => {
                    event.stopPropagation();
                    onMoveUp?.();
                }}
                class="flex h-5 w-5 items-center justify-center rounded bg-background/80 text-xs text-muted-foreground shadow-sm hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-30"
                aria-label="Move up"
            >
                ↑
            </button>
            <button
                type="button"
                disabled={!canMoveDown}
                onclick={(event) => {
                    event.stopPropagation();
                    onMoveDown?.();
                }}
                class="flex h-5 w-5 items-center justify-center rounded bg-background/80 text-xs text-muted-foreground shadow-sm hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-30"
                aria-label="Move down"
            >
                ↓
            </button>
        </div>
    {/if}
</div>
