<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import CalendarAgenda from '@/components/tasks/CalendarAgenda.svelte';
    import CalendarMonth from '@/components/tasks/CalendarMonth.svelte';
    import CalendarStats from '@/components/tasks/CalendarStats.svelte';
    import DayDetail from '@/components/tasks/DayDetail.svelte';
    import TaskViewToggle from '@/components/tasks/TaskViewToggle.svelte';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { todayInAppTimezone } from '@/lib/date';
    import type { CalendarDay, CalendarProps } from '@/types/tasks';

    let {
        month,
        grid,
        days,
        summary,
        molorTasks,
        users,
        filters,
        thresholds,
    }: CalendarProps = $props();

    const today = todayInAppTimezone();
    const emptyDay: CalendarDay = {
        today: [],
        done: [],
        rollback: [],
        planned: [],
    };

    let selectedDate = $state(today);
    let userId = $state<number | null>(null);
    let renderedMonth = $state<string | null>(null);

    // A new month from the server resets the selected day, but a user-filter
    // change keeps the current selection.
    $effect(() => {
        if (month !== renderedMonth) {
            renderedMonth = month;
            selectedDate = month === today.slice(0, 7) ? today : `${month}-01`;
        }
    });

    $effect(() => {
        userId = filters.user_id ?? null;
    });

    const selectedDay = $derived.by(() => days[selectedDate] ?? emptyDay);

    function visit(nextMonth: string, nextUserId: number | null = userId) {
        router.get(
            '/tasks/calendar',
            {
                month: nextMonth,
                ...(nextUserId ? { user_id: nextUserId } : {}),
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: [
                    'month',
                    'grid',
                    'days',
                    'summary',
                    'molorTasks',
                    'users',
                    'filters',
                    'thresholds',
                ],
            },
        );
    }

    function changeMonth(nextMonth: string) {
        selectedDate =
            nextMonth === today.slice(0, 7) ? today : `${nextMonth}-01`;
        visit(nextMonth);
    }

    function goToToday() {
        selectedDate = today;

        if (today.slice(0, 7) !== month) {
            visit(today.slice(0, 7));
        }
    }

    function changeUser(value: string) {
        userId = value ? Number(value) : null;
        visit(month, userId);
    }
</script>

<AppHead title="Task Calendar" />

<AppLayout
    breadcrumbs={[
        { title: 'Tasks', href: '/tasks' },
        { title: 'Calendar', href: '/tasks/calendar' },
    ]}
>
    <div class="flex h-full flex-col gap-3 p-4">
        <div
            class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"
        >
            <div>
                <div
                    class="text-[10px] uppercase tracking-[0.14em] text-muted-foreground"
                >
                    Tasks / Calendar
                </div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Task Calendar
                </h1>
                <p class="text-sm text-muted-foreground">
                    Daily activity, completed work, and tasks that slipped.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <TaskViewToggle active="calendar" {month} />

                <select
                    class="h-8 rounded border border-border bg-card px-2 pr-6 text-[11px] text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    value={userId ?? ''}
                    onchange={(event) => changeUser(event.currentTarget.value)}
                    aria-label="Filter by user"
                >
                    <option value="">All users</option>
                    {#each users as user (user.id)}
                        <option value={user.id}>{user.name}</option>
                    {/each}
                </select>
            </div>
        </div>

        <CalendarStats {summary} />

        {#if summary.completed + summary.today_entries + summary.rollbacks + summary.planned === 0}
            <p
                class="rounded border border-dashed border-border px-3 py-2 text-[11px] text-muted-foreground"
            >
                No activity this month. Tasks appear here once they enter the
                today board or are completed.
            </p>
        {/if}

        <div class="grid gap-2 xl:grid-cols-[minmax(0,1fr)_340px]">
            <CalendarMonth
                {month}
                {grid}
                {days}
                {selectedDate}
                {today}
                onChangeMonth={changeMonth}
                onToday={goToToday}
                onSelect={(date) => (selectedDate = date)}
            />

            <div class="hidden md:block xl:sticky xl:top-16 xl:self-start">
                <DayDetail
                    date={selectedDate}
                    day={selectedDay}
                    {molorTasks}
                    {thresholds}
                />
            </div>
        </div>

        <CalendarAgenda {month} {days} />

        <p class="flex items-center gap-1.5 text-[10px] text-muted-foreground">
            <span aria-hidden="true">i</span>
            “Rolled back” is counted from history entries where a task moved from
            today to todo, including the nightly reset.
        </p>
    </div>
</AppLayout>
