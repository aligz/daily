<script lang="ts">
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { getInitials } from '@/lib/initials';
    import { getUserColor } from '@/lib/users';
    import type { ChecklistTeamMember } from '@/types/tasks';

    let {
        members,
        onSelect,
    }: {
        members: ChecklistTeamMember[];
        onSelect: (id: number) => void;
    } = $props();
</script>

<section
    class="rounded-md border border-border bg-card"
    aria-labelledby="team-today-title"
>
    <header
        class="flex flex-wrap items-center gap-x-2 gap-y-1 border-b border-border px-3 py-2"
    >
        <span
            class="text-2xs uppercase tracking-[0.14em] text-muted-foreground"
        >
            Team
        </span>
        <h2
            id="team-today-title"
            class="text-base font-semibold tracking-tight"
        >
            Team today
        </h2>
        <span class="text-xs text-muted-foreground">
            · {members.length} developer{members.length === 1 ? '' : 's'}
        </span>
        <span class="ml-auto text-xs text-muted-foreground">
            Pick someone to read their routine.
        </span>
    </header>

    <ul class="grid gap-1 px-2 py-2 sm:grid-cols-2 xl:grid-cols-3">
        {#each members as member (member.id)}
            {@const resolved = member.done + member.skipped}
            {@const percent =
                member.total > 0
                    ? Math.round((resolved / member.total) * 100)
                    : 0}
            <li>
                <button
                    type="button"
                    class="flex w-full items-center gap-2 rounded-sm px-1.5 py-1 text-left transition-colors hover:bg-accent/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="Open {member.name}'s routine"
                    onclick={() => onSelect(member.id)}
                >
                    <Avatar class="h-6 w-6">
                        <AvatarFallback
                            class="text-2xs {getUserColor(member.name)}"
                        >
                            {getInitials(member.name)}
                        </AvatarFallback>
                    </Avatar>

                    <span class="min-w-0 flex-1 truncate text-sm">
                        {member.name}
                    </span>

                    {#if member.total === 0}
                        <span class="shrink-0 text-xs text-muted-foreground">
                            No routines
                        </span>
                    {:else}
                        <span
                            class="shrink-0 text-dense tabular-nums text-muted-foreground"
                        >
                            {resolved}/{member.total}
                        </span>
                        <span
                            class="h-1 w-16 shrink-0 overflow-hidden rounded-full bg-muted"
                            role="progressbar"
                            aria-valuemin={0}
                            aria-valuemax={100}
                            aria-valuenow={percent}
                            aria-label="{member.name} routine progress"
                        >
                            <span
                                class="block h-full bg-routine"
                                style={`width: ${percent}%`}
                            ></span>
                        </span>
                    {/if}
                </button>
            </li>
        {/each}
    </ul>
</section>
