<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import X from 'lucide-svelte/icons/x';
    import { fly } from 'svelte/transition';
    import { prefersReducedMotion } from '@/lib/motion';
    import { overlay } from '@/lib/overlay';
    import { cn } from '@/lib/utils';
    import { SHEET_CONTEXT, type SheetContext } from './context';

    let {
        side = 'right',
        class: className = '',
        label = 'Menu',
        children,
    }: {
        side?: 'right' | 'left' | 'top' | 'bottom';
        class?: string;
        label?: string;
        children?: Snippet;
    } = $props();

    const { open, setOpen } = getContext<SheetContext>(SHEET_CONTEXT);

    const sideClasses: Record<string, string> = {
        right: 'inset-y-0 right-0',
        left: 'inset-y-0 left-0',
        top: 'inset-x-0 top-0',
        bottom: 'inset-x-0 bottom-0',
    };

    const sizeClasses: Record<string, string> = {
        right: 'h-full w-3/4 sm:max-w-sm',
        left: 'h-full w-3/4 sm:max-w-sm',
        top: 'h-auto',
        bottom: 'h-auto',
    };

    const close = () => setOpen(false);

    const panelTransition = () => {
        if (prefersReducedMotion()) {
            return { duration: 0 };
        }

        const axis =
            side === 'left'
                ? { x: -320, y: 0 }
                : side === 'right'
                  ? { x: 320, y: 0 }
                  : side === 'top'
                    ? { x: 0, y: -320 }
                    : { x: 0, y: 320 };

        return { ...axis, duration: 260, opacity: 1 };
    };
</script>

{#if open()}
    <div class="fixed inset-0 z-50">
        <button
            type="button"
            class="fixed inset-0 border-0 bg-black/50"
            tabindex="-1"
            aria-label="Close menu"
            onclick={close}
        ></button>
        <div
            class={cn(
                'fixed relative flex flex-col gap-4 overflow-y-auto overscroll-contain border-none bg-background p-6 shadow-lg',
                sideClasses[side] ?? sideClasses.right,
                sizeClasses[side] ?? sizeClasses.right,
                className,
            )}
            role="dialog"
            aria-modal="true"
            aria-label={label}
            in:fly={panelTransition()}
            out:fly={panelTransition()}
            use:overlay={{ onEscape: close }}
        >
            {@render children?.()}
            <button
                type="button"
                class="ring-offset-background focus-visible:ring-ring absolute top-4 right-4 rounded-xs opacity-70 transition-opacity hover:opacity-100 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden disabled:pointer-events-none"
                aria-label="Close menu"
                onclick={close}
            >
                <X class="size-4" aria-hidden="true" />
                <span class="sr-only">Close menu</span>
            </button>
        </div>
    </div>
{/if}
