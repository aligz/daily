<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from 'lucide-svelte/icons/arrow-right';
    import CalendarDays from 'lucide-svelte/icons/calendar-days';
    import CircleCheckBig from 'lucide-svelte/icons/circle-check-big';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import ListChecks from 'lucide-svelte/icons/list-checks';
    import Moon from 'lucide-svelte/icons/moon';
    import AppHead from '@/components/AppHead.svelte';
    import AppLayout from '@/layouts/AppLayout.svelte';

    const toc = [
        { id: 'board', number: '01', title: 'The board' },
        { id: 'cards', number: '02', title: 'Reading a card' },
        { id: 'calendar', number: '03', title: 'The calendar' },
        { id: 'routine', number: '04', title: 'Your routine' },
        { id: 'team', number: '05', title: 'Working as a team' },
        { id: 'habits', number: '06', title: 'Habits that help' },
        { id: 'questions', number: '07', title: 'Quick answers' },
    ];

    const columns = [
        {
            name: 'Backlog',
            dot: 'bg-muted-foreground/50',
            border: 'border-l-muted-foreground/40',
            what: 'Things worth doing, not yet promised.',
            how: 'Park ideas here without committing to them.',
        },
        {
            name: 'Todo',
            dot: 'bg-orange-500',
            border: 'border-l-orange-500/60',
            what: 'Agreed work, waiting for a slot.',
            how: 'This is your queue. Pick from here when Today is empty.',
        },
        {
            name: 'Today',
            dot: 'bg-sky-500',
            border: 'border-l-sky-500/60',
            what: 'What you are doing right now.',
            how: 'Keep it to one to three cards you can actually finish.',
        },
        {
            name: 'Done',
            dot: 'bg-green-500',
            border: 'border-l-green-500/60',
            what: 'Finished work, grouped by finish date.',
            how: 'Drag a card here once it is really finished.',
        },
    ];

    const badges = [
        {
            chip: '▶ Focus today',
            chipClass:
                'border-sky-500/50 bg-sky-500/10 text-sky-700 dark:text-sky-300',
            meaning: 'The card sits in the Today column right now.',
        },
        {
            chip: '◇ Plan 12 Nov',
            chipClass:
                'border-dashed border-muted-foreground/60 text-muted-foreground',
            meaning:
                'The day you planned to pick it up. It also shows on the calendar.',
        },
        {
            chip: 'Stale 21d',
            chipClass:
                'border-amber-500/50 bg-amber-500/10 text-amber-700 dark:text-amber-300',
            meaning:
                'Nobody touched it for a long time (14 days by default). Split it, schedule it, or drop it.',
        },
        {
            chip: '4× today · 3↩',
            chipClass:
                'border-destructive/50 bg-destructive/10 text-destructive',
            meaning:
                'It keeps coming back: three or more visits to Today, or two or more slip-backs. A hint that it is too big.',
        },
        {
            chip: '✓ Done 26 Oct',
            chipClass:
                'border-green-500/50 bg-green-500/10 text-green-700 dark:text-green-300',
            meaning: 'When the task was finished.',
        },
    ];

    const habits = [
        {
            lead: 'Keep Today small.',
            body: 'One to three cards you can genuinely finish. Today is a promise, not a wishlist.',
        },
        {
            lead: 'Tidy up before you leave.',
            body: 'Finished to Done, unfinished back to Todo. Tomorrow starts clean, and the calendar stays honest.',
        },
        {
            lead: 'Plan tomorrow before you log off.',
            body: 'Set “Planned for” on the card you will start with. Future you will not have to remember.',
        },
        {
            lead: 'Put the ticket number in Reference.',
            body: 'A short code such as JIRA-123 on the card makes it searchable later, in the calendar too.',
        },
        {
            lead: 'Tick your routine before you go.',
            body: 'Could not do it? Use Skip and leave the one-line note. Skipping is honest; silence is not.',
        },
        {
            lead: 'Trust the badges.',
            body: 'Stale or delayed is not a scolding. It is a signal to split the task, schedule it, or drop it.',
        },
    ];

    const questions = [
        {
            q: 'A card I was working on vanished from Today.',
            a: 'At midnight (Jakarta time) the app moves whatever is left in Today back to Todo. Nothing is lost — the card is in Todo, and the calendar records the move as “rolled back”.',
        },
        {
            q: 'Why can’t I move or tick something?',
            a: 'You can only move your own cards. Routine boxes belong to their owner; heads can open other people’s routines, but only to look.',
        },
        {
            q: 'Why does the date picker refuse last week?',
            a: '“Planned for” is about the future, so past dates are disabled. If you need to keep older context, put it in the description.',
        },
        {
            q: 'I deleted a task. Why is it still on the calendar?',
            a: 'The calendar keeps history. A deleted task still appears, struck through and marked “(deleted)”, so past days stay accurate.',
        },
        {
            q: 'Where is my routine?',
            a: 'On the calendar page, in the violet bar above the monthly numbers. It is not on the board.',
        },
        {
            q: 'Can I bring back an archived routine?',
            a: 'Not in this version. Add it again as a new routine; the old entries stay visible on past days.',
        },
        {
            q: 'Does skipping a routine break my streak?',
            a: 'No. Done and skipped both count as handled. Only a day you never touched does.',
        },
        {
            q: 'Why do I see 0/3 before doing anything?',
            a: 'An untouched routine counts as pending, not completed. The number fills in as you tick boxes.',
        },
    ];
</script>

{#snippet heading(number: string, title: string)}
    <div class="flex items-baseline gap-2.5">
        <span class="font-mono text-[12px] tabular-nums text-muted-foreground"
            >{number}</span
        >
        <h2 class="text-[22px] font-semibold tracking-tight">{title}</h2>
    </div>
{/snippet}

{#snippet pill(href: string, label: string)}
    <Link
        {href}
        class="inline-flex items-center gap-1.5 rounded border border-border bg-card px-2.5 py-1.5 text-[13px] transition-colors hover:bg-accent"
    >
        {label}
        <ArrowRight class="h-3.5 w-3.5 text-muted-foreground" />
    </Link>
{/snippet}

<AppHead title="Guide" />

<AppLayout breadcrumbs={[{ title: 'Guide', href: '/guide' }]}>
    <div class="mx-auto w-full max-w-4xl px-4 py-6 pb-20">
        <header class="border-b border-border pb-6">
            <p
                class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground"
            >
                Guide
            </p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                How to use Daily
            </h1>
            <p
                class="mt-2 max-w-2xl text-[15px] leading-relaxed text-muted-foreground"
            >
                Three places, one habit: plan the day, do the work, look back.
                This page explains every screen in plain language. Read it once,
                then come back whenever something looks unfamiliar.
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                {@render pill('/tasks', 'Open the board')}
                {@render pill('/tasks/calendar', 'Open the calendar')}
            </div>
        </header>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded border border-border bg-card p-3.5">
                <LayoutGrid class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                <div class="mt-1.5 text-[14px] font-semibold">Board</div>
                <p class="text-[12.5px] leading-relaxed text-muted-foreground">
                    Where work waits and moves. You drag cards here.
                </p>
            </div>
            <div class="rounded border border-border bg-card p-3.5">
                <CalendarDays
                    class="h-4 w-4 text-green-600 dark:text-green-400"
                />
                <div class="mt-1.5 text-[14px] font-semibold">Calendar</div>
                <p class="text-[12.5px] leading-relaxed text-muted-foreground">
                    What actually happened on each day. It fills itself in.
                </p>
            </div>
            <div class="rounded border border-border bg-card p-3.5">
                <ListChecks
                    class="h-4 w-4 text-violet-600 dark:text-violet-400"
                />
                <div class="mt-1.5 text-[14px] font-semibold">Routine</div>
                <p class="text-[12.5px] leading-relaxed text-muted-foreground">
                    The few things you do every day. One click to tick them.
                </p>
            </div>
        </div>

        <nav
            aria-label="On this page"
            class="mt-7 flex flex-wrap items-center gap-x-1 gap-y-1 border-y border-border py-2"
        >
            {#each toc as item (item.id)}
                <a
                    href={`#${item.id}`}
                    class="inline-flex items-baseline gap-1.5 rounded px-1.5 py-1 text-[12.5px] text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <span class="font-mono text-[10.5px] tabular-nums"
                        >{item.number}</span
                    >
                    {item.title}
                </a>
            {/each}
        </nav>

        <div class="mt-8 flex flex-col gap-8">
            <section
                id="board"
                class="scroll-mt-24 border-t border-border pt-7 first:border-t-0 first:pt-0"
            >
                {@render heading('01', 'The board')}

                <p
                    class="mt-2 text-[14.5px] leading-relaxed text-muted-foreground"
                >
                    The board is where work moves. Each column is a stage; cards
                    travel left to right as work gets closer to done. A card
                    belongs to one person, and only that person moves it.
                </p>

                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    {#each columns as column (column.name)}
                        <div
                            class="rounded border border-border border-l-2 bg-card px-3 py-2.5 {column.border}"
                        >
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="h-1.5 w-1.5 rounded-sm {column.dot}"
                                    aria-hidden="true"
                                ></span>
                                <span class="text-[14px] font-semibold"
                                    >{column.name}</span
                                >
                            </div>
                            <p class="mt-1 text-[13px] leading-relaxed">
                                {column.what}
                            </p>
                            <p
                                class="mt-1 text-[12.5px] leading-relaxed text-muted-foreground"
                            >
                                {column.how}
                            </p>
                        </div>
                    {/each}
                </div>

                <h3 class="mt-6 text-[15px] font-semibold">Moving work</h3>
                <ol
                    class="mt-1.5 flex flex-col gap-1 text-[14px] leading-relaxed"
                >
                    <li>
                        <span class="font-medium">Drag</span> a card to another column.
                        The change saves as soon as you drop it.
                    </li>
                    <li>
                        <span class="font-medium">Hover</span> a card in Backlog or
                        Todo and use the ↑ ↓ buttons to set your own order inside
                        the column.
                    </li>
                    <li>
                        <span class="font-medium">Click</span> a card to open it,
                        edit the details, or delete it. Other people’s cards open
                        read-only.
                    </li>
                </ol>

                <h3 class="mt-6 text-[15px] font-semibold">Adding a task</h3>
                <p
                    class="mt-1 text-[14px] leading-relaxed text-muted-foreground"
                >
                    Use the New Task button at the top right. Only the title is
                    required.
                </p>
                <ul
                    class="mt-2 flex flex-col gap-1.5 text-[14px] leading-relaxed"
                >
                    <li>
                        <span class="font-medium">Title</span> — the outcome, not
                        the activity. “Ship the login fix”, not “Look at login”.
                    </li>
                    <li>
                        <span class="font-medium">Reference</span> — the ticket number
                        if there is one, such as JIRA-123. It appears on the card
                        and in the calendar.
                    </li>
                    <li>
                        <span class="font-medium">Planned for</span> — an optional
                        future date, for when you intend to start. It does not push
                        the card into Today, and past dates are disabled.
                    </li>
                    <li>
                        <span class="font-medium">Description</span> — any detail
                        your future self will need.
                    </li>
                </ul>

                <div
                    class="mt-4 flex gap-2.5 rounded border border-border bg-sky-500/5 px-3 py-2.5"
                >
                    <Moon
                        class="mt-0.5 h-4 w-4 shrink-0 text-sky-600 dark:text-sky-400"
                        aria-hidden="true"
                    />
                    <p class="text-[13px] leading-relaxed">
                        <span class="font-semibold"
                            >Every night at midnight (Jakarta time)</span
                        >
                        the app moves whatever is left in Today back to Todo. That
                        keeps Today meaningful, and the calendar records the move
                        so you can see what slipped.
                    </p>
                </div>
            </section>

            <section
                id="cards"
                class="scroll-mt-24 border-t border-border pt-7"
            >
                {@render heading('02', 'Reading a card')}

                <p
                    class="mt-2 text-[14.5px] leading-relaxed text-muted-foreground"
                >
                    Small chips tell you why a card is worth a look. A card
                    shows at most two of them. Hover a chip for the exact
                    numbers.
                </p>

                <div
                    class="mt-3 flex flex-col divide-y divide-border rounded border border-border"
                >
                    {#each badges as badge (badge.chip)}
                        <div
                            class="flex flex-col gap-1 px-3 py-2.5 sm:flex-row sm:items-baseline sm:gap-4"
                        >
                            <span
                                class="w-fit shrink-0 rounded-sm border px-1.5 py-0.5 text-[11px] leading-none {badge.chipClass}"
                            >
                                {badge.chip}
                            </span>
                            <span
                                class="text-[13px] leading-relaxed text-muted-foreground"
                            >
                                {badge.meaning}
                            </span>
                        </div>
                    {/each}
                </div>

                <p class="mt-2 text-[12.5px] text-muted-foreground">
                    “Stale” counts days without a change. “Delayed” counts trips
                    through Today and slip-backs — currently 3 visits or 2
                    slip-backs.
                </p>
            </section>

            <section
                id="calendar"
                class="scroll-mt-24 border-t border-border pt-7"
            >
                {@render heading('03', 'The calendar')}

                <p
                    class="mt-2 text-[14.5px] leading-relaxed text-muted-foreground"
                >
                    The calendar is written by the board, so you never maintain
                    it twice. Move cards and tick routines; the days fill
                    themselves in. Switch to it with the Board / Calendar toggle
                    at the top right of the Tasks page.
                </p>

                <h3 class="mt-5 text-[15px] font-semibold">Reading a month</h3>
                <ul
                    class="mt-1.5 flex flex-col gap-1 text-[14px] leading-relaxed"
                >
                    <li>
                        Weeks start on Monday. Today is highlighted and weekends
                        are tinted.
                    </li>
                    <li>
                        A day shows up to three entries: a blue bar means the
                        card entered Today, green means completed, a dashed ◇
                        means planned. The small number is how many entries that
                        day has.
                    </li>
                    <li>
                        A red edge on top plus “↩ n rolled back” means work
                        slipped back to Todo that day.
                    </li>
                    <li>
                        The violet line at the bottom is that day’s routine
                        progress.
                    </li>
                </ul>

                <h3 class="mt-5 text-[15px] font-semibold">Opening one day</h3>
                <p
                    class="mt-1 text-[14px] leading-relaxed text-muted-foreground"
                >
                    Click a day to open Day detail on the right. It groups the
                    day into Today entries, Completed, Planned, and Rolled back,
                    with names, initials, references, and times. Deleted tasks
                    stay visible, struck through and marked “(deleted)”. If the
                    day has routine activity, it appears at the top with each
                    item and the day’s note.
                </p>

                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <div
                        class="rounded border border-border bg-card px-3 py-2.5"
                    >
                        <div class="text-[13px] font-semibold">
                            The four numbers
                        </div>
                        <p
                            class="mt-1 text-[12.5px] leading-relaxed text-muted-foreground"
                        >
                            Completed this month, Today entries, Rolled back,
                            and Most delayed — a one-line health check of the
                            month, not a scoreboard.
                        </p>
                    </div>
                    <div
                        class="rounded border border-border bg-card px-3 py-2.5"
                    >
                        <div class="text-[13px] font-semibold">
                            Moving around
                        </div>
                        <p
                            class="mt-1 text-[12.5px] leading-relaxed text-muted-foreground"
                        >
                            Use the arrows for the previous or next month, and
                            Today to jump back. With a day focused, the arrow
                            keys walk the grid.
                        </p>
                    </div>
                </div>

                <p
                    class="mt-3 text-[13px] leading-relaxed text-muted-foreground"
                >
                    On a phone, the grid is replaced by an Agenda list of the
                    same days, in order. Nothing on this page is editable — it
                    is a record. If something looks wrong, fix the card on the
                    board.
                </p>
            </section>

            <section
                id="routine"
                class="scroll-mt-24 border-t border-border pt-7"
            >
                {@render heading('04', 'Your routine')}

                <p
                    class="mt-2 text-[14.5px] leading-relaxed text-muted-foreground"
                >
                    Routine is the short list of things you do every day:
                    standup, review a pull request, check alerts, write your
                    update. The same list every day, one click to tick. It lives
                    in the violet bar at the top of the calendar page and always
                    shows today, whichever month you are browsing.
                </p>

                <div class="mt-4 flex flex-col gap-2">
                    <div
                        class="rounded border border-border bg-card px-3 py-2.5"
                    >
                        <div class="text-[13.5px] font-semibold">
                            Tick and untick
                        </div>
                        <p
                            class="mt-0.5 text-[13px] leading-relaxed text-muted-foreground"
                        >
                            One click marks it done. Click the same box again
                            and it goes back to pending.
                        </p>
                    </div>
                    <div
                        class="rounded border border-border bg-card px-3 py-2.5"
                    >
                        <div class="text-[13.5px] font-semibold">
                            Skip a day it does not apply
                        </div>
                        <p
                            class="mt-0.5 text-[13px] leading-relaxed text-muted-foreground"
                        >
                            Open ⋯ and choose Skip — for leave, a holiday, or a
                            day you were blocked. It is counted as handled,
                            tagged “skipped”, and it does not break your streak.
                        </p>
                    </div>
                    <div
                        class="rounded border border-border bg-card px-3 py-2.5"
                    >
                        <div class="text-[13.5px] font-semibold">
                            Leave one note for the day
                        </div>
                        <p
                            class="mt-0.5 text-[13px] leading-relaxed text-muted-foreground"
                        >
                            Use Note for the thing that got in the way. One line
                            per day, shown in the bar and on that day in the
                            calendar — so “blocked on staging” is visible months
                            later.
                        </p>
                    </div>
                    <div
                        class="rounded border border-border bg-card px-3 py-2.5"
                    >
                        <div class="text-[13.5px] font-semibold">
                            Add, rename, reorder, archive
                        </div>
                        <p
                            class="mt-0.5 text-[13px] leading-relaxed text-muted-foreground"
                        >
                            Add routine sits at the bottom of the bar and starts
                            counting today. ⋯ lets you edit the name, move it up
                            or down, or archive it. Archiving hides it from
                            today onward and keeps its history; there is no
                            un-archive in this version.
                        </p>
                    </div>
                </div>

                <div
                    class="mt-3 flex flex-col gap-1 text-[13.5px] leading-relaxed text-muted-foreground"
                >
                    <p>
                        <span class="font-medium text-foreground"
                            >🔥 streak</span
                        >
                        counts days in a row where everything active that day was
                        done or skipped. Today does not break it while the day is
                        still going.
                    </p>
                    <p>
                        <span class="font-medium text-foreground">n/m</span>
                        is handled out of total. Untouched routines count as pending,
                        which is why a fresh day starts at 0.
                    </p>
                    <p>
                        The bar only checks today; past days are a record. And
                        routines never appear on the board — habits are not
                        tickets.
                    </p>
                </div>
            </section>

            <section id="team" class="scroll-mt-24 border-t border-border pt-7">
                {@render heading('05', 'Working as a team')}

                <p
                    class="mt-2 text-[14.5px] leading-relaxed text-muted-foreground"
                >
                    The filter at the top right switches between All users and
                    one person. On the board it hides everyone else’s cards; on
                    the calendar it narrows the days, the numbers, and the
                    routine bar.
                </p>

                <ul
                    class="mt-3 flex flex-col gap-2 text-[14px] leading-relaxed"
                >
                    <li>
                        <span class="font-medium">Heads</span> also see each developer’s
                        routine progress next to their name, and can open someone
                        else’s routine to read it. That view is locked: heads cannot
                        tick boxes for other people, so the record stays honest.
                    </li>
                    <li>
                        <span class="font-medium">Everyone else</span> can filter
                        what they are looking at, but the routine bar stays their
                        own.
                    </li>
                    <li>
                        <span class="font-medium">Reports</span> shows completed work
                        per person and the average time from picking a task up to
                        finishing it, for the last 7 to 90 days.
                    </li>
                    <li>
                        <span class="font-medium">Feature Requests</span> is a separate
                        list for requests coming from other divisions, with its own
                        priorities and deadlines. It is not part of your daily board.
                    </li>
                </ul>
            </section>

            <section
                id="habits"
                class="scroll-mt-24 border-t border-border pt-7"
            >
                {@render heading('06', 'Habits that help')}

                <p
                    class="mt-2 text-[14.5px] leading-relaxed text-muted-foreground"
                >
                    The app is simple on purpose. These six habits are what make
                    it useful instead of just another tab.
                </p>

                <ol class="mt-3 flex flex-col gap-2.5">
                    {#each habits as habit, index (habit.lead)}
                        <li class="flex gap-2.5">
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-muted font-mono text-[11px] text-muted-foreground"
                                >{index + 1}</span
                            >
                            <p class="text-[14px] leading-relaxed">
                                <span class="font-semibold">{habit.lead}</span>
                                {habit.body}
                            </p>
                        </li>
                    {/each}
                </ol>
            </section>

            <section
                id="questions"
                class="scroll-mt-24 border-t border-border pt-7"
            >
                {@render heading('07', 'Quick answers')}

                <div class="mt-3 flex flex-col gap-1.5">
                    {#each questions as item (item.q)}
                        <details
                            class="group rounded border border-border bg-card px-3 py-2"
                        >
                            <summary
                                class="flex cursor-pointer list-none items-center gap-2 text-[13.5px] font-medium select-none"
                            >
                                <span
                                    class="text-muted-foreground transition-transform group-open:rotate-90"
                                    aria-hidden="true">›</span
                                >
                                {item.q}
                            </summary>
                            <p
                                class="mt-1.5 pl-4 text-[13px] leading-relaxed text-muted-foreground"
                            >
                                {item.a}
                            </p>
                        </details>
                    {/each}
                </div>

                <div
                    class="mt-5 flex items-center gap-2 rounded border border-dashed border-border px-3 py-2.5"
                >
                    <CircleCheckBig
                        class="h-4 w-4 shrink-0 text-green-600 dark:text-green-400"
                        aria-hidden="true"
                    />
                    <p
                        class="text-[13px] leading-relaxed text-muted-foreground"
                    >
                        Still unsure about something? Ask in the team chat, or
                        open the board and look at a real card — most answers
                        are visible there.
                    </p>
                </div>
            </section>
        </div>
    </div>
</AppLayout>
