<script lang="ts">
    import CalendarDay from '@/components/tasks/CalendarDay.svelte';
    import type {
        CalendarDay as CalendarDayData,
        ChecklistDay,
    } from '@/types/tasks';
    import { addMonths, formatMonth } from '@/lib/date';

    let {
        month,
        grid,
        days,
        checklistByDate,
        selectedDate,
        today,
        onChangeMonth,
        onToday,
        onSelect,
    }: {
        month: string;
        grid: { start: string; end: string };
        days: Record<string, CalendarDayData>;
        checklistByDate: Record<string, ChecklistDay>;
        selectedDate: string;
        today: string;
        onChangeMonth: (month: string) => void;
        onToday: () => void;
        onSelect: (date: string) => void;
    } = $props();

    const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    const weeks = $derived.by(() => {
        const result: string[][] = [];
        let cursor = new Date(`${grid.start}T00:00:00Z`).getTime();
        const end = new Date(`${grid.end}T00:00:00Z`).getTime();

        while (cursor <= end) {
            const week: string[] = [];

            for (let index = 0; index < 7; index += 1) {
                week.push(new Date(cursor).toISOString().slice(0, 10));
                cursor += 86_400_000;
            }

            result.push(week);
        }

        return result;
    });

    const cells = $derived(weeks.flat());

    const emptyDay = (): CalendarDayData => ({
        today: [],
        done: [],
        rollback: [],
        planned: [],
    });
    const dayFor = (date: string): CalendarDayData => days[date] ?? emptyDay();

</script>

<section
    class="hidden overflow-hidden rounded border border-border bg-card md:block"
    aria-label="Task calendar"
>
    <div class="flex flex-wrap items-center gap-2 border-b border-border px-3 py-2">
        <button
            type="button"
            class="inline-flex h-6 w-6 items-center justify-center rounded-sm border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
            onclick={() => onChangeMonth(addMonths(month, -1))}
            aria-label="Previous month"
        >
            ‹
        </button>
        <h2 class="min-w-[130px] text-sm font-semibold tracking-tight">
            {formatMonth(month)}
        </h2>
        <button
            type="button"
            class="inline-flex h-6 w-6 items-center justify-center rounded-sm border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
            onclick={() => onChangeMonth(addMonths(month, 1))}
            aria-label="Next month"
        >
            ›
        </button>
        <button
            type="button"
            class="rounded-sm border border-primary/40 bg-primary/10 px-2 py-0.5 text-[10px] text-primary transition-colors hover:bg-primary/15"
            onclick={onToday}
        >
            Today
        </button>

        <div
            class="ml-auto flex flex-wrap items-center gap-x-3 gap-y-1 text-[9.5px] text-muted-foreground"
        >
            <span class="flex items-center gap-1">
                <i class="h-1.5 w-1.5 rounded-full bg-sky-500" aria-hidden="true"></i>
                Today entries
            </span>
            <span class="flex items-center gap-1">
                <i class="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true"></i>
                Completed
            </span>
            <span class="flex items-center gap-1">
                <i class="h-0.5 w-2.5 bg-destructive" aria-hidden="true"></i>
                Rolled back
            </span>
        </div>
    </div>

    <div class="grid grid-cols-7 border-b border-border">
        {#each weekdays as weekday}
            <div
                class="border-r border-border px-2 py-1.5 text-[9px] uppercase tracking-[0.1em] text-muted-foreground last:border-r-0"
            >
                {weekday}
            </div>
        {/each}
    </div>

    <div class="grid grid-cols-7" data-calendar-grid>
        {#each cells as date (date)}
            <CalendarDay
                {date}
                {month}
                day={dayFor(date)}
                checklistDay={checklistByDate[date] ?? null}
                selected={date === selectedDate}
                {today}
                {onSelect}
            />
        {/each}
    </div>

    <div class="flex flex-wrap items-center gap-2 px-3 py-2 text-[10px] text-muted-foreground">
        <span><kbd class="rounded border border-border px-1">←</kbd> <kbd class="rounded border border-border px-1">→</kbd> change month</span>
        <span aria-hidden="true">·</span>
        <span>Click a day for details</span>
        <span class="ml-auto">Timezone: Asia/Jakarta</span>
    </div>
</section>
