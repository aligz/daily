<script lang="ts">
    import Check from 'lucide-svelte/icons/check';
    import ChevronDown from 'lucide-svelte/icons/chevron-down';
    import Users from 'lucide-svelte/icons/users';
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

    const MENU_WIDTH = 224;
    const GAP = 6;
    const EDGE = 8;

    let open = $state(false);
    let trigger = $state<HTMLButtonElement | null>(null);
    let menu = $state<HTMLDivElement | null>(null);
    let position = $state({ top: 0, left: 0 });

    const selected = $derived(
        users.find((user) => user.id === selectedId) ?? null,
    );

    function itemElements(): HTMLButtonElement[] {
        if (!menu) return [];

        return Array.from(
            menu.querySelectorAll<HTMLButtonElement>('[role="menuitemradio"]'),
        );
    }

    function focusItem(index: number) {
        const items = itemElements();

        if (items.length === 0) return;

        items[(index + items.length) % items.length].focus();
    }

    function openMenu() {
        if (trigger) {
            const rect = trigger.getBoundingClientRect();

            position = {
                left: Math.max(
                    EDGE,
                    Math.min(
                        rect.right - MENU_WIDTH,
                        window.innerWidth - MENU_WIDTH - EDGE,
                    ),
                ),
                top: rect.bottom + GAP,
            };
        }

        open = true;
        queueMicrotask(() => focusItem(0));
    }

    function closeMenu(returnFocus = true) {
        open = false;

        if (returnFocus) {
            trigger?.focus();
        }
    }

    function pick(id: number | null) {
        open = false;
        onchange(id);
    }

    function handleMenuKeydown(event: KeyboardEvent) {
        const items = itemElements();
        const currentIndex = items.indexOf(
            document.activeElement as HTMLButtonElement,
        );

        switch (event.key) {
            case 'Escape':
                event.preventDefault();
                event.stopPropagation();
                closeMenu();
                break;
            case 'ArrowDown':
                event.preventDefault();
                focusItem(currentIndex + 1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                focusItem(currentIndex - 1);
                break;
            case 'Home':
                event.preventDefault();
                focusItem(0);
                break;
            case 'End':
                event.preventDefault();
                focusItem(items.length - 1);
                break;
            case 'Tab':
                closeMenu(false);
                break;
        }
    }

    // The menu is fixed-positioned so the board's scroll container cannot clip it.
    $effect(() => {
        if (!open || !menu || !trigger) return;

        const height = menu.offsetHeight;
        const rect = trigger.getBoundingClientRect();
        const overflowsBelow =
            rect.bottom + GAP + height > window.innerHeight - EDGE;

        position = {
            left: Math.max(
                EDGE,
                Math.min(
                    rect.right - MENU_WIDTH,
                    window.innerWidth - MENU_WIDTH - EDGE,
                ),
            ),
            top: overflowsBelow
                ? Math.max(EDGE, rect.top - GAP - height)
                : rect.bottom + GAP,
        };
    });

    $effect(() => {
        if (!open) return;

        const close = () => closeMenu(false);

        window.addEventListener('scroll', close, true);
        window.addEventListener('resize', close);

        return () => {
            window.removeEventListener('scroll', close, true);
            window.removeEventListener('resize', close);
        };
    });
</script>

<div class="relative">
    <button
        type="button"
        bind:this={trigger}
        data-user-filter-trigger
        class="relative flex h-8 w-8 items-center justify-center rounded-full border border-border bg-card transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        aria-haspopup="menu"
        aria-expanded={open}
        aria-label={label}
        title={label}
        onclick={(event) => {
            event.stopPropagation();

            if (open) {
                closeMenu();
            } else {
                openMenu();
            }
        }}
        onkeydown={(event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                event.stopPropagation();
                openMenu();
            }
        }}
    >
        {#if selected}
            <Avatar class="h-6 w-6">
                <AvatarFallback
                    class="rounded-full text-2xs {getUserColor(selected.name)}"
                >
                    {getInitials(selected.name)}
                </AvatarFallback>
            </Avatar>
        {:else}
            <span
                class="flex h-6 w-6 items-center justify-center rounded-full bg-muted text-muted-foreground"
            >
                <Users class="h-3.5 w-3.5" aria-hidden="true" />
            </span>
        {/if}

        <span
            class="absolute -right-0.5 -bottom-0.5 flex h-3.5 w-3.5 items-center justify-center rounded-full border border-border bg-card text-muted-foreground"
            aria-hidden="true"
        >
            <ChevronDown class="h-2.5 w-2.5" />
        </span>
    </button>

    {#if open}
        <div
            bind:this={menu}
            class="fixed z-50 overflow-hidden rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-lg"
            style:top={`${position.top}px`}
            style:left={`${position.left}px`}
            style:width={`${MENU_WIDTH}px`}
            role="menu"
            aria-label={label}
            tabindex="-1"
            use:clickOutside={{
                handler: () => closeMenu(false),
                ignore: '[data-user-filter-trigger]',
            }}
            onkeydown={handleMenuKeydown}
        >
            {#each [{ id: null, name: 'All users' }, ...users.map( (user) => ({ id: user.id as number | null, name: user.name }), )] as option (option.id ?? 'all')}
                {@const isActive = option.id === selectedId}
                <button
                    type="button"
                    role="menuitemradio"
                    aria-checked={isActive}
                    tabindex="-1"
                    class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-xs hover:bg-accent focus-visible:bg-accent focus-visible:outline-none {isActive
                        ? 'bg-accent'
                        : ''}"
                    onclick={() => pick(option.id)}
                >
                    {#if option.id === null}
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"
                        >
                            <Users class="h-3.5 w-3.5" aria-hidden="true" />
                        </span>
                    {:else}
                        <Avatar class="h-6 w-6">
                            <AvatarFallback
                                class="rounded-full text-2xs {getUserColor(
                                    option.name,
                                )}"
                            >
                                {getInitials(option.name)}
                            </AvatarFallback>
                        </Avatar>
                    {/if}

                    <span class="min-w-0 flex-1 truncate">{option.name}</span>

                    {#if option.id !== null && progress[option.id]}
                        <span
                            class="shrink-0 text-2xs tabular-nums text-muted-foreground"
                        >
                            {progress[option.id].done +
                                progress[option.id].skipped}/{progress[
                                option.id
                            ].total}
                        </span>
                    {/if}

                    {#if isActive}
                        <Check
                            class="h-3.5 w-3.5 shrink-0 text-foreground"
                            aria-hidden="true"
                        />
                    {/if}
                </button>
            {/each}
        </div>
    {/if}
</div>
