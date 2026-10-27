<script lang="ts">
    import type { Snippet } from 'svelte';
    import TaskViewToggle from '@/components/tasks/TaskViewToggle.svelte';
    import UserFilter from '@/components/tasks/UserFilter.svelte';
    import type { UserFilterOption, UserFilterProgress } from '@/types/tasks';

    let {
        active,
        eyebrow,
        title,
        subtitle,
        users,
        selectedUserId = null,
        onUserChange,
        month,
        progress = {},
        badge,
        actions,
    }: {
        active: 'board' | 'calendar';
        eyebrow: string;
        title: string;
        subtitle: string;
        users: UserFilterOption[];
        selectedUserId?: number | null;
        onUserChange: (id: number | null) => void;
        month?: string;
        progress?: Record<number, UserFilterProgress>;
        badge?: Snippet;
        actions?: Snippet;
    } = $props();
</script>

<header class="mb-4">
    <div class="text-3xs uppercase tracking-[0.14em] text-muted-foreground">
        {eyebrow}
    </div>

    <div
        class="mt-0.5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
    >
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight">{title}</h1>
            <p class="mt-1 text-sm text-muted-foreground">{subtitle}</p>
        </div>

        <div class="flex flex-col gap-2 lg:items-end">
            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                <TaskViewToggle {active} {month} />
                <UserFilter
                    {users}
                    selectedId={selectedUserId}
                    {progress}
                    onchange={onUserChange}
                />
            </div>

            {#if badge || actions}
                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                    {#if badge}
                        {@render badge()}
                    {/if}
                    {#if actions}
                        {@render actions()}
                    {/if}
                </div>
            {/if}
        </div>
    </div>
</header>
