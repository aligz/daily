<script lang="ts">
    import { formatDateShort } from '@/lib/date';
    import type { BoardTask, TaskMetric } from '@/types/tasks';

    let {
        task,
        metric,
    }: {
        task: BoardTask;
        metric?: TaskMetric;
    } = $props();

    type Badge = {
        label: string;
        className: string;
        title: string;
    };

    const styles = {
        green: 'border-green-500/40 bg-green-500/10 text-green-700 dark:text-green-300',
        red: 'border-destructive/40 bg-destructive/10 text-destructive',
        amber: 'border-amber-500/40 bg-amber-500/10 text-amber-700 dark:text-amber-300',
        cyan: 'border-sky-500/40 bg-sky-500/10 text-sky-700 dark:text-sky-300',
    };

    const badges = $derived.by(() => {
        const list: Badge[] = [];

        if (task.status === 'done') {
            if (task.completed_at) {
                const completed = formatDateShort(
                    task.completed_at.slice(0, 10),
                );

                list.push({
                    label: `✓ Done ${completed}`,
                    className: styles.green,
                    title: `Completed on ${completed}`,
                });
            }

            return list;
        }

        if (metric?.is_molor) {
            list.push({
                label: `${metric.today_count}× today · ${metric.rollback_count}↩`,
                className: styles.red,
                title: `Entered today ${metric.today_count} times, rolled back ${metric.rollback_count} times. Counted from task history.`,
            });
        }

        if (metric?.is_stale && metric.stuck_days !== null) {
            list.push({
                label: `Stale ${metric.stuck_days}d`,
                className: styles.amber,
                title: `No status change for ${metric.stuck_days} days.`,
            });
        }

        if (task.status === 'today' && !metric?.is_molor) {
            list.push({
                label: '▶ Focus today',
                className: styles.cyan,
                title: 'Current daily focus.',
            });
        }

        return list.slice(0, 2);
    });
</script>

{#if badges.length > 0}
    <div class="flex flex-wrap gap-1 px-3 pb-2">
        {#each badges as badge (badge.label)}
            <span
                class="inline-flex items-center rounded-sm border px-1 py-0.5 text-[9px] leading-none {badge.className}"
                title={badge.title}
            >
                {badge.label}
            </span>
        {/each}
    </div>
{/if}
