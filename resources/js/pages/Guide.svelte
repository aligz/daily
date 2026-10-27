<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from 'lucide-svelte/icons/arrow-right';
    import CalendarDays from 'lucide-svelte/icons/calendar-days';
    import CircleCheckBig from 'lucide-svelte/icons/circle-check-big';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import ListChecks from 'lucide-svelte/icons/list-checks';
    import Moon from 'lucide-svelte/icons/moon';
    import { onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { en } from '@/lib/guide/en';
    import { id as idCopy } from '@/lib/guide/id';
    import type { GuideLang, GuideLine } from '@/lib/guide/types';

    const STORAGE_KEY = 'daily.guide.lang';

    const columnStyles = [
        {
            dot: 'bg-muted-foreground/50',
            border: 'border-l-muted-foreground/40',
        },
        { dot: 'bg-orange-500', border: 'border-l-orange-500/60' },
        { dot: 'bg-sky-500', border: 'border-l-sky-500/60' },
        { dot: 'bg-green-500', border: 'border-l-green-500/60' },
    ];

    const legendStyles = [
        'border-sky-500/50 bg-sky-500/10 text-sky-700 dark:text-sky-300',
        'border-dashed border-muted-foreground/60 text-muted-foreground',
        'border-amber-500/50 bg-amber-500/10 text-amber-700 dark:text-amber-300',
        'border-destructive/50 bg-destructive/10 text-destructive',
        'border-green-500/50 bg-green-500/10 text-green-700 dark:text-green-300',
    ];

    const summaryIcons = [LayoutGrid, CalendarDays, ListChecks];
    const summaryTones = [
        'text-sky-600 dark:text-sky-400',
        'text-green-600 dark:text-green-400',
        'text-violet-600 dark:text-violet-400',
    ];

    let lang = $state<GuideLang>('en');

    const copy = $derived(lang === 'id' ? idCopy : en);

    const toc = $derived(
        copy.toc.map((item, index) => ({
            ...item,
            number: String(index + 1).padStart(2, '0'),
        })),
    );

    const columns = $derived(
        copy.board.columns.map((column, index) => ({
            ...column,
            ...columnStyles[index],
        })),
    );

    const legend = $derived(
        copy.cards.legend.map((item, index) => ({
            ...item,
            chipClass: legendStyles[index],
        })),
    );

    function detectLang(): GuideLang | null {
        const param = new URLSearchParams(window.location.search).get('lang');

        if (param === 'id' || param === 'en') {
            return param;
        }

        try {
            const stored = window.localStorage.getItem(STORAGE_KEY);

            if (stored === 'id' || stored === 'en') {
                return stored;
            }
        } catch {
            // Private mode and locked-down browsers can refuse storage.
        }

        return window.navigator.language?.toLowerCase().startsWith('id')
            ? 'id'
            : null;
    }

    function choose(next: GuideLang) {
        if (next === lang) {
            return;
        }

        lang = next;
        document.documentElement.lang = next;

        try {
            window.localStorage.setItem(STORAGE_KEY, next);
        } catch {
            // Language still switches for this visit.
        }

        const url = new URL(window.location.href);
        url.searchParams.set('lang', next);
        window.history.replaceState(window.history.state, '', url.toString());
    }

    onMount(() => {
        const detected = detectLang();

        if (detected && detected !== lang) {
            lang = detected;
            document.documentElement.lang = detected;
        }
    });
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

<!-- eslint-disable svelte/no-useless-mustaches -->
{#snippet line(item: GuideLine, weight: string)}{#if item.lead}<span
            class="text-foreground {weight}">{item.lead}</span
        >{' '}{/if}{item.body}{/snippet}
<!-- eslint-enable svelte/no-useless-mustaches -->

<AppHead title={copy.title} />

<AppLayout breadcrumbs={[{ title: copy.eyebrow, href: '/guide' }]}>
    <div class="mx-auto w-full max-w-4xl px-4 py-6 pb-20">
        <header class="border-b border-border pb-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p
                        class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground"
                    >
                        {copy.eyebrow}
                    </p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                        {copy.title}
                    </h1>
                </div>

                <div
                    class="flex shrink-0 items-center gap-0.5 rounded border border-border bg-card p-0.5"
                    role="group"
                    aria-label={copy.languageLabel}
                >
                    <button
                        type="button"
                        aria-pressed={lang === 'id'}
                        onclick={() => choose('id')}
                        class="rounded-sm px-2 py-0.5 text-[12px] font-semibold transition-colors {lang ===
                        'id'
                            ? 'bg-foreground text-background'
                            : 'text-muted-foreground hover:bg-accent hover:text-foreground'}"
                    >
                        ID
                    </button>
                    <button
                        type="button"
                        aria-pressed={lang === 'en'}
                        onclick={() => choose('en')}
                        class="rounded-sm px-2 py-0.5 text-[12px] font-semibold transition-colors {lang ===
                        'en'
                            ? 'bg-foreground text-background'
                            : 'text-muted-foreground hover:bg-accent hover:text-foreground'}"
                    >
                        EN
                    </button>
                </div>
            </div>

            <p
                class="mt-2 max-w-2xl text-[15px] leading-relaxed text-muted-foreground"
            >
                {copy.intro}
            </p>

            <div class="mt-4 flex flex-wrap gap-2">
                {@render pill('/tasks', copy.openBoard)}
                {@render pill('/tasks/calendar', copy.openCalendar)}
            </div>
        </header>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            {#each copy.summary as part, index (part.title)}
                {@const Icon = summaryIcons[index]}
                <div class="rounded border border-border bg-card p-3.5">
                    <Icon class="h-4 w-4 {summaryTones[index]}" />
                    <div class="mt-1.5 text-[14px] font-semibold">
                        {part.title}
                    </div>
                    <p
                        class="text-[12.5px] leading-relaxed text-muted-foreground"
                    >
                        {part.body}
                    </p>
                </div>
            {/each}
        </div>

        <nav
            aria-label={copy.tocLabel}
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
                {@render heading('01', copy.board.title)}

                <p
                    class="mt-2 text-[14.5px] leading-relaxed text-muted-foreground"
                >
                    {copy.board.intro}
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

                <h3 class="mt-6 text-[15px] font-semibold">
                    {copy.board.movingTitle}
                </h3>
                <ol
                    class="mt-1.5 flex flex-col gap-1 text-[14px] leading-relaxed"
                >
                    {#each copy.board.moving as item, index (index)}
                        <li>{@render line(item, 'font-medium')}</li>
                    {/each}
                </ol>

                <h3 class="mt-6 text-[15px] font-semibold">
                    {copy.board.addingTitle}
                </h3>
                <p
                    class="mt-1 text-[14px] leading-relaxed text-muted-foreground"
                >
                    {copy.board.addingIntro}
                </p>
                <ul
                    class="mt-2 flex flex-col gap-1.5 text-[14px] leading-relaxed"
                >
                    {#each copy.board.fields as field (field.label)}
                        <li>
                            <span class="font-medium">{field.label}</span>
                            — {field.text}
                        </li>
                    {/each}
                </ul>

                <div
                    class="mt-4 flex gap-2.5 rounded border border-border bg-sky-500/5 px-3 py-2.5"
                >
                    <Moon
                        class="mt-0.5 h-4 w-4 shrink-0 text-sky-600 dark:text-sky-400"
                        aria-hidden="true"
                    />
                    <div class="min-w-0">
                        <p class="text-[13px] font-semibold">
                            {copy.board.resetTitle}
                        </p>
                        <p class="mt-0.5 text-[13px] leading-relaxed">
                            {copy.board.resetBody}
                        </p>
                    </div>
                </div>
            </section>

            <section
                id="cards"
                class="scroll-mt-24 border-t border-border pt-7"
            >
                {@render heading('02', copy.cards.title)}

                <p
                    class="mt-2 text-[14.5px] leading-relaxed text-muted-foreground"
                >
                    {copy.cards.intro}
                </p>

                <div
                    class="mt-3 flex flex-col divide-y divide-border rounded border border-border"
                >
                    {#each legend as badge (badge.chip)}
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
                    {copy.cards.footnote}
                </p>
            </section>

            <section
                id="calendar"
                class="scroll-mt-24 border-t border-border pt-7"
            >
                {@render heading('03', copy.calendar.title)}

                <p
                    class="mt-2 text-[14.5px] leading-relaxed text-muted-foreground"
                >
                    {copy.calendar.intro}
                </p>

                <h3 class="mt-5 text-[15px] font-semibold">
                    {copy.calendar.monthTitle}
                </h3>
                <ul
                    class="mt-1.5 flex flex-col gap-1 text-[14px] leading-relaxed"
                >
                    {#each copy.calendar.month as item, index (index)}
                        <li>{item}</li>
                    {/each}
                </ul>

                <h3 class="mt-5 text-[15px] font-semibold">
                    {copy.calendar.dayTitle}
                </h3>
                <p
                    class="mt-1 text-[14px] leading-relaxed text-muted-foreground"
                >
                    {copy.calendar.day}
                </p>

                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    {#each copy.calendar.boxes as box (box.title)}
                        <div
                            class="rounded border border-border bg-card px-3 py-2.5"
                        >
                            <div class="text-[13px] font-semibold">
                                {box.title}
                            </div>
                            <p
                                class="mt-1 text-[12.5px] leading-relaxed text-muted-foreground"
                            >
                                {box.body}
                            </p>
                        </div>
                    {/each}
                </div>

                <p
                    class="mt-3 text-[13px] leading-relaxed text-muted-foreground"
                >
                    {copy.calendar.footer}
                </p>
            </section>

            <section
                id="routine"
                class="scroll-mt-24 border-t border-border pt-7"
            >
                {@render heading('04', copy.routine.title)}

                <p
                    class="mt-2 text-[14.5px] leading-relaxed text-muted-foreground"
                >
                    {copy.routine.intro}
                </p>

                <div class="mt-4 flex flex-col gap-2">
                    {#each copy.routine.blocks as block (block.title)}
                        <div
                            class="rounded border border-border bg-card px-3 py-2.5"
                        >
                            <div class="text-[13.5px] font-semibold">
                                {block.title}
                            </div>
                            <p
                                class="mt-0.5 text-[13px] leading-relaxed text-muted-foreground"
                            >
                                {block.body}
                            </p>
                        </div>
                    {/each}
                </div>

                <div
                    class="mt-3 flex flex-col gap-1 text-[13.5px] leading-relaxed text-muted-foreground"
                >
                    {#each copy.routine.footnotes as note, index (index)}
                        <p>{@render line(note, 'font-medium')}</p>
                    {/each}
                </div>
            </section>

            <section id="team" class="scroll-mt-24 border-t border-border pt-7">
                {@render heading('05', copy.team.title)}

                <p
                    class="mt-2 text-[14.5px] leading-relaxed text-muted-foreground"
                >
                    {copy.team.intro}
                </p>

                <ul
                    class="mt-3 flex flex-col gap-2 text-[14px] leading-relaxed"
                >
                    {#each copy.team.items as item, index (index)}
                        <li>{@render line(item, 'font-medium')}</li>
                    {/each}
                </ul>
            </section>

            <section
                id="habits"
                class="scroll-mt-24 border-t border-border pt-7"
            >
                {@render heading('06', copy.habits.title)}

                <p
                    class="mt-2 text-[14.5px] leading-relaxed text-muted-foreground"
                >
                    {copy.habits.intro}
                </p>

                <ol class="mt-3 flex flex-col gap-2.5">
                    {#each copy.habits.items as habit, index (habit.lead)}
                        <li class="flex gap-2.5">
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-muted font-mono text-[11px] text-muted-foreground"
                                >{index + 1}</span
                            >
                            <p class="text-[14px] leading-relaxed">
                                {@render line(habit, 'font-semibold')}
                            </p>
                        </li>
                    {/each}
                </ol>
            </section>

            <section
                id="questions"
                class="scroll-mt-24 border-t border-border pt-7"
            >
                {@render heading('07', copy.questions.title)}

                <div class="mt-3 flex flex-col gap-1.5">
                    {#each copy.questions.items as item (item.q)}
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
                        {copy.questions.outro}
                    </p>
                </div>
            </section>
        </div>
    </div>
</AppLayout>
