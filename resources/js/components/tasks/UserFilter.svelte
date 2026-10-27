<script lang="ts">
    import ChevronDown from 'lucide-svelte/icons/chevron-down';
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { clickOutside } from '@/lib/clickOutside';
    import { getInitials } from '@/lib/initials';
    import { getUserColor } from '@/lib/users';
    import type { UserFilterOption, UserFilterProgress } from '@/types/tasks';

    let {
        users,
        selectedId = null,
        progress = {},
        onchange,
        label = 'Filter by user',
    }: {
        users: UserFilterOption[];
        selectedId?: number | null;
        progress?: Record<number, UserFilterProgress>;
        onchange: (id: number | null) => void;
        label?: string;
    } = $props();

    let open = $state(false);

    const selected = $derived(
        users.find((user) => user.id === selectedId) ?? null,
    );

    function pick(id: number | null) {
        open = false;
        onchange(id);
    }
</script>

<svelte:window
    onkeydown={(event) => {
        if (event.key === 'Escape') open = false;
    }}
/>

<div class="relative">
    <button
        type="button"
        class="inline-flex h-8 items-center gap-2 rounded border border-border bg-card px-2 text-xs transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        data-user-filter-trigger
        aria-haspopup="menu"
        aria-expanded={open}
        aria-label={label}
        onclick={() => (open = !open)}
    >
        {#if selected}
            <Avatar class="h-5 w-5">
                <AvatarFallback class="text-2xs {getUserColor(selected.name)}">
                    {getInitials(selected.name)}
                </AvatarFallback>
            </Avatar>
            <span class="max-w-[120px] truncate">{selected.name}</span>
        {:else}
            <span>All users</span>
        {/if}

        <ChevronDown class="h-3.5 w-3.5 opacity-50" />
    </button>

    {#if open}
        <div
            class="absolute right-0 z-50 mt-1 min-w-[210px] overflow-hidden rounded border border-border bg-popover p-1 text-popover-foreground shadow-md"
            data-user-filter-menu
            use:clickOutside={{
                handler: () => (open = false),
                ignore: '[data-user-filter-trigger]',
            }}
        >
            <button
                type="button"
                class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-xs hover:bg-accent"
                onclick={() => pick(null)}
            >
                <span class="text-muted-foreground">All users</span>
            </button>

            <div class="h-px bg-border"></div>

            {#each users as user (user.id)}
                <button
                    type="button"
                    class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-xs hover:bg-accent {selectedId ===
                    user.id
                        ? 'bg-accent'
                        : ''}"
                    onclick={() => pick(user.id)}
                >
                    <Avatar class="h-5 w-5">
                        <AvatarFallback
                            class="text-2xs {getUserColor(user.name)}"
                        >
                            {getInitials(user.name)}
                        </AvatarFallback>
                    </Avatar>
                    <span class="truncate">{user.name}</span>

                    {#if progress[user.id]}
                        <span
                            class="ml-auto shrink-0 text-2xs tabular-nums text-muted-foreground"
                        >
                            {progress[user.id].done +
                                progress[user.id].skipped}/{progress[user.id]
                                .total}
                        </span>
                    {/if}
                </button>
            {/each}
        </div>
    {/if}
</div>
