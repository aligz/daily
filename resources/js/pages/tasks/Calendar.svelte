<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import RoutineBar from '@/components/checklist/RoutineBar.svelte';
    import CalendarAgenda from '@/components/tasks/CalendarAgenda.svelte';
    import CalendarMonth from '@/components/tasks/CalendarMonth.svelte';
    import CalendarStats from '@/components/tasks/CalendarStats.svelte';
    import DayDetail from '@/components/tasks/DayDetail.svelte';
    import TaskHeader from '@/components/tasks/TaskHeader.svelte';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { todayInAppTimezone } from '@/lib/date';
    import type { CalendarDay, CalendarProps } from '@/types/tasks';

    let {
        month,
        grid,
        days,
        summary,
        checklist,
        checklist_by_date,
        checklist_team,
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

    const teamProgress = $derived(
        Object.fromEntries(
            checklist_team.map((member) => [
                member.id,
                {
                    done: member.done,
                    skipped: member.skipped,
                    total: member.total,
                },
            ]),
        ),
    );

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
                    'checklist',
                    'checklist_by_date',
                    'checklist_team',
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

    function changeUser(next: number | null) {
        userId = next;
        visit(month, next);
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
        <TaskHeader
            active="calendar"
            eyebrow="Tasks / Calendar"
            title="Task Calendar"
            subtitle="Daily activity, completed work, and your routine."
            {users}
            selectedUserId={userId}
            progress={teamProgress}
            onUserChange={changeUser}
            {month}
        />

        <RoutineBar {checklist} />

        <CalendarStats {summary} />

        {#if summary.completed + summary.today_entries + summary.rollbacks + summary.planned === 0}
            <p
                class="rounded-md border border-dashed border-border px-3 py-2 text-dense text-muted-foreground"
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
                checklistByDate={checklist_by_date}
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
                    checklistDay={checklist_by_date[selectedDate] ?? null}
                    {molorTasks}
                    {thresholds}
                />
            </div>
        </div>

        <CalendarAgenda {month} {days} checklistByDate={checklist_by_date} />

        <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
            <span aria-hidden="true">i</span>
            “Rolled back” is counted from history entries where a task moved from
            today to todo, including the nightly reset.
        </p>
    </div>
</AppLayout>
