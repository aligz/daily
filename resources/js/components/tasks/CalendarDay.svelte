<script lang="ts">
    import type {
        CalendarDay as CalendarDayData,
        ChecklistDay,
    } from '@/types/tasks';
    import { addDays, dayNumber, formatDay, isWeekend } from '@/lib/date';
    import { cn } from '@/lib/utils';

    let {
        date,
        month,
        day,
        checklistDay = null,
        selected,
        today,
        onSelect,
    }: {
        date: string;
        month: string;
        day: CalendarDayData;
        checklistDay?: ChecklistDay | null;
        selected: boolean;
        today: string;
        onSelect: (date: string) => void;
    } = $props();

    const chips = $derived(
        [
            ...day.today.map((event) => ({
                kind: 'today' as const,
                ...event,
            })),
            ...day.done.map((event) => ({
                kind: 'done' as const,
                ...event,
            })),
            ...day.planned.map((event) => ({
                kind: 'planned' as const,
                ...event,
            })),
        ].slice(0, 3),
    );

    const total = $derived(
        day.today.length +
            day.done.length +
            day.rollback.length +
            day.planned.length,
    );
    const extra = $derived(
        day.today.length + day.done.length + day.planned.length - chips.length,
    );
    const inMonth = $derived(date.slice(0, 7) === month);
    const weekend = $derived(isWeekend(date));
    const isToday = $derived(date === today);

    const checklistResolved = $derived(
        checklistDay ? checklistDay.done + checklistDay.skipped : 0,
    );
    const checklistPercent = $derived(
        checklistDay && checklistDay.total > 0
            ? Math.round((checklistResolved / checklistDay.total) * 100)
            : 0,
    );

    const chipClass = (kind: 'today' | 'done') =>
        kind === 'today'
            ? 'border-sky-500/70 bg-sky-500/10 text-sky-700 dark:text-sky-300'
            : 'border-green-500/70 bg-green-500/10 text-green-700 dark:text-green-300';

    function handleKeydown(event: KeyboardEvent) {
        const steps: Record<string, number> = {
            ArrowLeft: -1,
            ArrowRight: 1,
            ArrowUp: -7,
            ArrowDown: 7,
        };
        const step = steps[event.key];

        if (step === undefined) {
            return;
        }

        event.preventDefault();

        const next = addDays(date, step);
        (event.currentTarget as HTMLElement)
            .closest<HTMLElement>('[data-calendar-grid]')
            ?.querySelector<HTMLElement>(`[data-date="${next}"]`)
            ?.focus();
        onSelect(next);
    }
</script>

<button
    type="button"
    data-date={date}
    onclick={() => onSelect(date)}
    onkeydown={handleKeydown}
    class={cn(
        'relative flex min-h-[104px] flex-col gap-1 border-b border-r border-border p-1.5 text-left transition-colors hover:bg-accent/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset focus-visible:outline-none',
        !inMonth && 'opacity-40',
        weekend && inMonth && 'bg-muted/30',
        selected && 'bg-primary/5 ring-1 ring-primary ring-inset',
    )}
    aria-label={`${formatDay(date)}: ${day.today.length} today entries, ${day.done.length} completed, ${day.rollback.length} rolled back, ${day.planned.length} planned${checklistDay ? `, ${checklistResolved} of ${checklistDay.total} routines` : ''}`}
    aria-pressed={selected}
>
    {#if day.rollback.length > 0}
        <span class="absolute inset-x-0 top-0 h-0.5 bg-destructive"></span>
    {/if}

    <span class="flex items-center justify-between gap-1">
        <span
            class={cn(
                'text-[10px] tabular-nums',
                isToday
                    ? 'inline-flex h-5 min-w-5 items-center justify-center rounded-sm bg-primary px-1 font-semibold text-primary-foreground'
                    : 'px-0.5 text-muted-foreground',
            )}
        >
            {dayNumber(date)}
        </span>
        {#if isToday}
            <span class="text-[8px] uppercase tracking-[0.1em] text-primary">
                Today
            </span>
        {:else if total > 0}
            <span class="text-[9px] tabular-nums text-muted-foreground">
                {total}
            </span>
        {/if}
    </span>

    {#each chips as event (event.kind + event.id + event.at)}
        <span
            class={cn(
                'flex w-full items-center gap-1 truncate rounded-sm px-1 py-0.5 text-[10px]',
                event.kind === 'planned'
                    ? 'border border-dashed border-muted-foreground/50 text-muted-foreground'
                    : cn('border-l-2', chipClass(event.kind)),
                event.deleted && 'line-through opacity-60',
            )}
            title={`${event.title} — ${event.user.name}${event.kind === 'planned' ? ' (planned)' : ''}`}
        >
            <span class="truncate">
                {event.kind === 'planned' ? '◇ ' : ''}{event.title}
            </span>
        </span>
    {/each}

    {#if extra > 0}
        <span class="text-[9px] text-muted-foreground">+{extra} more</span>
    {/if}

    {#if checklistDay || day.rollback.length > 0}
        <span class="mt-auto flex flex-col gap-0.5">
            {#if checklistDay}
                <span
                    class="flex items-center gap-1 text-[9px] text-violet-600 dark:text-violet-300"
                    title={`Routine: ${checklistResolved} of ${checklistDay.total} done`}
                >
                    <span aria-hidden="true">▣</span>
                    <span
                        class="h-[3px] flex-1 overflow-hidden rounded bg-violet-500/20"
                    >
                        <span
                            class="block h-full bg-violet-500"
                            style={`width: ${checklistPercent}%`}
                        ></span>
                    </span>
                    <span class="tabular-nums">
                        {checklistResolved}/{checklistDay.total}
                    </span>
                </span>
            {/if}

            {#if day.rollback.length > 0}
                <span class="flex items-center gap-1 text-[9px] text-destructive">
                    ↩ {day.rollback.length} rolled back
                </span>
            {/if}
        </span>
    {/if}
</button>
