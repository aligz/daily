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
    import { getUserColor } from '@/lib/users';
    import { cn } from '@/lib/utils';
    import type { BoardTask, TaskMetric } from '@/types/tasks';
    import TaskBadges from './TaskBadges.svelte';
    import TaskCardMenu from './TaskCardMenu.svelte';

    let {
        task,
        mine,
        metric,
        canMoveUp = false,
        canMoveDown = false,
        onOpen,
        onDragStart,
        onMoveUp,
        onMoveDown,
        onMove,
    }: {
        task: BoardTask;
        mine: boolean;
        metric?: TaskMetric;
        canMoveUp?: boolean;
        canMoveDown?: boolean;
        onOpen: (task: BoardTask) => void;
        onDragStart: (event: DragEvent, task: BoardTask) => void;
        onMoveUp?: () => void;
        onMoveDown?: () => void;
        onMove: (status: BoardTask['status']) => void;
    } = $props();

    const titleId = $derived(`task-title-${task.id}`);

    function handleKeydown(event: KeyboardEvent) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            onOpen(task);
        }
    }
</script>

<article
    class="group relative"
    draggable={mine}
    ondragstart={(event) => onDragStart(event, task)}
>
    <div
        role="button"
        tabindex="0"
        aria-labelledby={titleId}
        class={cn(
            'block rounded-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
            mine ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer',
        )}
        onclick={() => onOpen(task)}
        onkeydown={handleKeydown}
    >
        <Card
            class="shadow-none hover:shadow-md transition-shadow border-transparent py-2 gap-0"
        >
            <CardHeader class="p-3 pb-1 pr-9 space-y-0">
                <div class="flex items-start justify-between gap-2">
                    <CardTitle
                        id={titleId}
                        class="min-w-0 text-sm font-medium leading-snug break-words"
                    >
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
                    <CardDescription
                        class="text-[10px] font-mono"
                        translate="no"
                    >
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
    </div>

    {#if mine}
        <div class="absolute right-1 top-1 z-10">
            <TaskCardMenu
                {task}
                {canMoveUp}
                {canMoveDown}
                {onMoveUp}
                {onMoveDown}
                {onMove}
                onEdit={() => onOpen(task)}
            />
        </div>
    {/if}
</article>
