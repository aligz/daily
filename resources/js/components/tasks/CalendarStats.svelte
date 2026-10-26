<script lang="ts">
    import type { CalendarSummary } from '@/types/tasks';

    let { summary }: { summary: CalendarSummary } = $props();
</script>

<div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded border border-border bg-card px-3.5 py-3">
        <div
            class="text-[12px] uppercase tracking-[0.12em] text-muted-foreground"
        >
            Completed this month
        </div>
        <div class="mt-0.5 text-2xl font-semibold tracking-tight">
            {summary.completed}
        </div>
        <div class="text-[13px] text-muted-foreground">
            Tasks marked done
        </div>
    </div>

    <div class="rounded border border-border bg-card px-3.5 py-3">
        <div
            class="text-[12px] uppercase tracking-[0.12em] text-muted-foreground"
        >
            Today entries
        </div>
        <div class="mt-0.5 text-2xl font-semibold tracking-tight">
            {summary.today_entries}
        </div>
        <div class="text-[13px] text-muted-foreground">
            Times a task became the daily focus
        </div>
    </div>

    <div class="rounded border border-border bg-card px-3.5 py-3">
        <div
            class="text-[12px] uppercase tracking-[0.12em] text-muted-foreground"
        >
            Rolled back
        </div>
        <div class="mt-0.5 text-2xl font-semibold tracking-tight">
            {summary.rollbacks}
        </div>
        <div class="text-[13px] text-muted-foreground">
            Today → todo transitions
        </div>
    </div>

    <div class="rounded border border-border bg-card px-3.5 py-3">
        <div
            class="text-[12px] uppercase tracking-[0.12em] text-muted-foreground"
        >
            Most delayed
        </div>
        {#if summary.top_molor}
            <div class="mt-0.5 flex items-baseline gap-1.5">
                <span
                    class="text-2xl font-semibold tracking-tight text-destructive"
                >
                    {summary.top_molor.today_count}×
                </span>
                <span class="text-[12.5px] text-muted-foreground">
                    {summary.top_molor.rollback_count} rollbacks
                </span>
            </div>
            <div
                class="truncate text-[13.5px]"
                title={summary.top_molor.title}
            >
                {summary.top_molor.title}
            </div>
        {:else}
            <div class="mt-0.5 text-2xl font-semibold tracking-tight">
                —
            </div>
            <div class="text-[13px] text-muted-foreground">
                No delayed tasks
            </div>
        {/if}
    </div>
</div>
