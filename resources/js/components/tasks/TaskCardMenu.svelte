<script lang="ts">
    import MoreHorizontal from 'lucide-svelte/icons/more-horizontal';
    import { clickOutside } from '@/lib/clickOutside';
    import type { BoardTask } from '@/types/tasks';

    let {
        task,
        onMove,
        onEdit,
    }: {
        task: BoardTask;
        onMove: (status: BoardTask['status']) => void;
        onEdit: () => void;
    } = $props();

    const columns: { value: BoardTask['status']; label: string }[] = [
        { value: 'backlog', label: 'Planning' },
        { value: 'todo', label: 'Todo' },
        { value: 'today', label: 'Today' },
        { value: 'done', label: 'Done' },
    ];

    const targets = $derived(
        columns.filter((column) => column.value !== task.status),
    );

    const MENU_WIDTH = 176;
    const GAP = 4;
    const EDGE = 8;

    let open = $state(false);
    let trigger = $state<HTMLButtonElement | null>(null);
    let menu = $state<HTMLDivElement | null>(null);
    let position = $state({ top: 0, left: 0 });

    function itemElements(): HTMLButtonElement[] {
        if (!menu) return [];

        return Array.from(
            menu.querySelectorAll<HTMLButtonElement>('[role="menuitem"]'),
        ).filter((element) => !element.disabled);
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
                // Right-aligned to the trigger, kept inside the viewport.
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

        // Focus moves into the menu once it is in the DOM.
        queueMicrotask(() => focusItem(0));
    }

    // The menu is fixed-positioned so it is never clipped by the column's
    // scroll container nor painted under the next card's trigger.
    $effect(() => {
        if (!open || !menu || !trigger) return;

        const menuHeight = menu.offsetHeight;
        const rect = trigger.getBoundingClientRect();
        const overflowsBelow =
            rect.bottom + GAP + menuHeight > window.innerHeight - EDGE;

        position = {
            left: Math.max(
                EDGE,
                Math.min(
                    rect.right - MENU_WIDTH,
                    window.innerWidth - MENU_WIDTH - EDGE,
                ),
            ),
            top: overflowsBelow
                ? Math.max(EDGE, rect.top - GAP - menuHeight)
                : rect.bottom + GAP,
        };
    });

    // Scrolling invalidates the fixed coordinates, so close instead of drifting.
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

    function closeMenu(returnFocus = true) {
        open = false;

        if (returnFocus) {
            trigger?.focus();
        }
    }

    function move(status: BoardTask['status'], event: Event) {
        event.stopPropagation();
        closeMenu(false);
        onMove(status);
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
</script>

<div class="relative">
    <button
        type="button"
        bind:this={trigger}
        class="flex h-6 w-6 items-center justify-center rounded-sm text-muted-foreground opacity-70 transition-colors hover:bg-accent hover:text-foreground hover:opacity-100 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        aria-haspopup="menu"
        aria-expanded={open}
        aria-label={`Actions for ${task.title}`}
        data-task-menu-trigger
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
        <MoreHorizontal class="h-4 w-4" aria-hidden="true" />
    </button>

    {#if open}
        <div
            bind:this={menu}
            class="fixed z-50 overflow-hidden rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-lg"
            style:top={`${position.top}px`}
            style:left={`${position.left}px`}
            style:width={`${MENU_WIDTH}px`}
            role="menu"
            aria-label={`Actions for ${task.title}`}
            tabindex="-1"
            use:clickOutside={{
                handler: () => closeMenu(false),
                ignore: '[data-task-menu-trigger]',
            }}
            onkeydown={handleMenuKeydown}
        >
            {#each targets as target (target.value)}
                <button
                    type="button"
                    role="menuitem"
                    tabindex="-1"
                    class="block w-full rounded-sm px-2 py-1.5 text-left text-dense hover:bg-accent focus-visible:bg-accent focus-visible:outline-none"
                    onclick={(event) => move(target.value, event)}
                >
                    Move to {target.label}
                </button>
            {/each}

            <div class="my-1 h-px bg-border" aria-hidden="true"></div>

            <button
                type="button"
                role="menuitem"
                tabindex="-1"
                class="block w-full rounded-sm px-2 py-1.5 text-left text-dense hover:bg-accent focus-visible:bg-accent focus-visible:outline-none"
                onclick={(event) => {
                    event.stopPropagation();
                    closeMenu(false);
                    onEdit();
                }}
            >
                Edit details
            </button>
        </div>
    {/if}
</div>
