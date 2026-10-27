<script lang="ts">
    import {
        formatDateShort,
        formatMonth,
        formatTime,
        formatWeekday,
    } from '@/lib/date';
    import type { CalendarDay, ChecklistDay } from '@/types/tasks';

    let {
        month,
        days,
        checklistByDate = {},
    }: {
        month: string;
        days: Record<string, CalendarDay>;
        checklistByDate?: Record<string, ChecklistDay>;
    } = $props();

    const agendaDays = $derived.by(() =>
        Object.entries(days)
            .filter(
                ([date, day]) =>
                    date.startsWith(month) &&
                    (day.today.length +
                        day.done.length +
                        day.rollback.length +
                        day.planned.length >
                        0 ||
                        Boolean(checklistByDate[date])),
            )
            .sort(([first], [second]) => first.localeCompare(second)),
    );
</script>

<div class="rounded-md border border-border bg-card md:hidden">
    <div
        class="border-b border-border px-3 py-2 text-xs uppercase tracking-[0.12em] text-muted-foreground"
    >
        Agenda · {formatMonth(month)}
    </div>

    {#if agendaDays.length === 0}
        <p class="px-3 py-6 text-center text-xs text-muted-foreground">
            No activity this month.
        </p>
    {:else}
        {#each agendaDays as [date, day] (date)}
            <div class="border-b border-border px-3 py-2 last:border-b-0">
                <div class="mb-1 flex items-baseline gap-2">
                    <span class="text-sm font-semibold">
                        {formatDateShort(date)}
                    </span>
                    <span
                        class="text-2xs uppercase tracking-[0.1em] text-muted-foreground"
                    >
                        {formatWeekday(date)}
                    </span>
                    {#if day.rollback.length > 0}
                        <span class="ml-auto text-2xs text-destructive">
                            ↩ {day.rollback.length}
                        </span>
                    {/if}
                </div>

                {#if checklistByDate[date]}
                    <div
                        class="mb-1 flex items-center gap-1.5 text-xs text-routine"
                    >
                        <span aria-hidden="true">▣</span>
                        <span class="shrink-0 tabular-nums">
                            {checklistByDate[date].done +
                                checklistByDate[date].skipped}/{checklistByDate[
                                date
                            ].total}
                        </span>
                        {#if checklistByDate[date].note}
                            <span
                                class="min-w-0 truncate text-2xs text-muted-foreground italic"
                            >
                                “{checklistByDate[date].note}”
                            </span>
                        {/if}
                    </div>
                {/if}

                <div class="flex flex-col gap-1">
                    {#each day.today as event (event.at + event.id)}
                        <div class="flex items-center gap-1.5 text-dense">
                            <span
                                class="h-1.5 w-1.5 shrink-0 rounded-full bg-info"
                                aria-hidden="true"
                            ></span>
                            <span class="truncate">{event.title}</span>
                            <span
                                class="ml-auto shrink-0 text-2xs tabular-nums text-muted-foreground"
                            >
                                {formatTime(event.at)}
                            </span>
                        </div>
                    {/each}
                    {#each day.done as event (event.at + event.id)}
                        <div class="flex items-center gap-1.5 text-dense">
                            <span
                                class="h-1.5 w-1.5 shrink-0 rounded-full bg-success"
                                aria-hidden="true"
                            ></span>
                            <span class="truncate">{event.title}</span>
                            <span
                                class="ml-auto shrink-0 text-2xs tabular-nums text-muted-foreground"
                            >
                                {formatTime(event.at)}
                            </span>
                        </div>
                    {/each}
                    {#each day.planned as event (event.at + event.id)}
                        <div
                            class="flex items-center gap-1.5 text-dense text-muted-foreground"
                        >
                            <span class="shrink-0" aria-hidden="true">◇</span>
                            <span class="truncate">{event.title}</span>
                            <span
                                class="ml-auto shrink-0 text-2xs text-muted-foreground"
                            >
                                planned
                            </span>
                        </div>
                    {/each}
                    {#each day.rollback as event (event.at + event.id)}
                        <div
                            class="flex items-center gap-1.5 text-dense text-destructive"
                        >
                            <span class="shrink-0" aria-hidden="true">↩</span>
                            <span class="truncate">{event.title}</span>
                            <span
                                class="ml-auto shrink-0 text-2xs tabular-nums"
                            >
                                {formatTime(event.at)}
                            </span>
                        </div>
                    {/each}
                </div>
            </div>
        {/each}
    {/if}
</div>
