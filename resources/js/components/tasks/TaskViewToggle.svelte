<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Calendar from 'lucide-svelte/icons/calendar';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import { cn } from '@/lib/utils';
    import { calendar as tasksCalendar, index as tasksIndex } from '@/routes/tasks';

    let {
        active,
        month,
    }: {
        active: 'board' | 'calendar';
        month?: string;
    } = $props();

    const base =
        'inline-flex items-center gap-1.5 rounded-sm px-2.5 py-1 text-[11px] transition-colors';
    const activeClasses =
        'bg-muted text-foreground shadow-[inset_0_0_0_1px_var(--border)]';
    const inactiveClasses = 'text-muted-foreground hover:text-foreground';
</script>

<nav
    class="inline-flex items-center gap-0.5 rounded border border-border bg-card p-0.5"
    aria-label="Task view"
>
    <Link
        href={tasksIndex.url()}
        class={cn(base, active === 'board' ? activeClasses : inactiveClasses)}
        aria-current={active === 'board' ? 'page' : undefined}
    >
        <LayoutGrid class="h-3 w-3" />
        Board
    </Link>
    <Link
        href={tasksCalendar.url({ query: month ? { month } : undefined })}
        class={cn(base, active === 'calendar' ? activeClasses : inactiveClasses)}
        aria-current={active === 'calendar' ? 'page' : undefined}
    >
        <Calendar class="h-3 w-3" />
        Calendar
    </Link>
</nav>
