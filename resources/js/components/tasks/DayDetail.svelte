<script lang="ts">
    import { getInitials } from '@/lib/initials';
    import { formatDay, formatTime } from '@/lib/date';
    import type {
        CalendarDay,
        CalendarEvent,
        MolorTask,
        CalendarThresholds,
    } from '@/types/tasks';

    let {
        date,
        day,
        molorTasks,
        thresholds,
    }: {
        date: string;
        day: CalendarDay;
        molorTasks: MolorTask[];
        thresholds: CalendarThresholds;
    } = $props();

    const molorById = $derived(new Map(molorTasks.map((task) => [task.id, task])));

    function isMolor(taskId: number): boolean {
        const task = molorById.get(taskId);

        return (
            !!task &&
            (task.today_count >= thresholds.today ||
                task.rollback_count >= thresholds.rollback)
        );
    }

    const sections = $derived.by(() => {
        const list: {
            key: string;
            label: string;
            dot: string;
            items: CalendarEvent[];
        }[] = [
            {
                key: 'today',
                label: 'Today entries',
                dot: 'bg-sky-500',
                items: day.today,
            },
            {
                key: 'done',
                label: 'Completed',
                dot: 'bg-green-500',
                items: day.done,
            },
            {
                key: 'planned',
                label: 'Planned',
                dot: 'bg-muted-foreground',
                items: day.planned,
            },
            {
                key: 'rollback',
                label: 'Rolled back',
                dot: 'bg-destructive',
                items: day.rollback,
            },
        ];

        return list;
    });
</script>

<div class="flex flex-col overflow-hidden rounded border border-border bg-card">
    <div class="border-b border-border px-3.5 py-3">
        <div class="text-[9px] uppercase tracking-[0.14em] text-muted-foreground">
            Day detail
        </div>
        <div class="mt-0.5 text-sm font-semibold tracking-tight">
            {formatDay(date)}
        </div>
        <div class="mt-2 flex flex-wrap gap-1.5">
            {#if day.today.length > 0}
                <span class="rounded-sm border border-sky-500/40 bg-sky-500/10 px-1.5 py-0.5 text-[9px] text-sky-700 dark:text-sky-300">
                    ▶ {day.today.length} today
                </span>
            {/if}
            {#if day.done.length > 0}
                <span class="rounded-sm border border-green-500/40 bg-green-500/10 px-1.5 py-0.5 text-[9px] text-green-700 dark:text-green-300">
                    ✓ {day.done.length} done
                </span>
            {/if}
            {#if day.planned.length > 0}
                <span class="rounded-sm border border-dashed border-muted-foreground/50 px-1.5 py-0.5 text-[9px] text-muted-foreground">
                    ◇ {day.planned.length} planned
                </span>
            {/if}
            {#if day.rollback.length > 0}
                <span class="rounded-sm border border-destructive/40 bg-destructive/10 px-1.5 py-0.5 text-[9px] text-destructive">
                    ↩ {day.rollback.length} rolled back
                </span>
            {/if}
            {#if day.today.length + day.done.length + day.rollback.length + day.planned.length === 0}
                <span class="rounded-sm border border-border px-1.5 py-0.5 text-[9px] text-muted-foreground">
                    No activity
                </span>
            {/if}
        </div>
    </div>

    <div class="max-h-[420px] overflow-y-auto py-1">
        {#each sections as section (section.key)}
            <div class="px-3.5 pt-2 pb-1">
                <div class="mb-1 flex items-center gap-1.5 text-[9px] uppercase tracking-[0.12em] text-muted-foreground">
                    <i class="h-1.5 w-1.5 rounded-sm {section.dot}" aria-hidden="true"></i>
                    {section.label}
                    <span class="ml-auto rounded-sm border border-border px-1 text-[9px] tracking-normal">
                        {section.items.length}
                    </span>
                </div>

                {#if section.items.length === 0}
                    <div class="px-1 py-1 text-[10px] text-muted-foreground">—</div>
                {:else}
                    {#each section.items as event (event.at + event.id)}
                        <div class="flex items-start gap-2 rounded-sm px-1 py-1 hover:bg-accent/40">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-muted text-[8px] font-semibold text-muted-foreground">
                                {getInitials(event.user.name)}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[11px]" title={event.title}>
                                    {event.title}
                                    {#if event.deleted}
                                        <span class="ml-1 text-[9px] text-muted-foreground">(deleted)</span>
                                    {/if}
                                </div>
                                <div class="flex items-center gap-1 text-[9px] text-muted-foreground">
                                    <span class="font-mono">{event.reference ?? '—'}</span>
                                    <span aria-hidden="true">·</span>
                                    <span>{event.user.name}</span>
                                    {#if section.key !== 'rollback' && section.key !== 'planned' && isMolor(event.id)}
                                        <span class="ml-1 rounded-sm border border-destructive/40 bg-destructive/10 px-1 text-[8px] text-destructive">
                                            delayed
                                        </span>
                                    {/if}
                                </div>
                            </div>
                            <span class="pt-0.5 text-[9px] tabular-nums text-muted-foreground">
                                {#if section.key === 'planned'}
                                    ◇
                                {:else}
                                    {formatTime(event.at)}
                                {/if}
                            </span>
                        </div>
                    {/each}
                {/if}
            </div>
        {/each}
    </div>

    <div class="border-t border-border px-3.5 py-2.5">
        <div class="text-[9px] uppercase tracking-[0.12em] text-muted-foreground">
            Most delayed this month
        </div>
        <div class="mt-1.5 flex flex-col gap-1.5">
            {#if molorTasks.length === 0}
                <p class="text-[10px] text-muted-foreground">No delayed tasks.</p>
            {:else}
                {#each molorTasks as task (task.id)}
                    <div class="flex items-center gap-1.5">
                        <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-muted text-[7px] font-semibold text-muted-foreground">
                            {getInitials(task.user.name)}
                        </span>
                        <span class="min-w-0 flex-1 truncate text-[10px]" title={task.title}>
                            {task.title}
                        </span>
                        <span class="whitespace-nowrap text-[9px] text-destructive">
                            {task.today_count}× / {task.rollback_count}↩
                        </span>
                    </div>
                {/each}
            {/if}
        </div>
    </div>

    <div class="border-t border-border px-3.5 py-1.5 text-[9px] text-muted-foreground">
        Counts come from task_histories.
    </div>
</div>
