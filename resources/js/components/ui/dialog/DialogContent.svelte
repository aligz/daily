<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import { overlay } from '@/lib/overlay';
    import { cn } from '@/lib/utils';
    import { DIALOG_CONTEXT, type DialogContext } from './context';

    let { class: className = '', children }: { class?: string; children?: Snippet } = $props();

    const { open, setOpen, titleId, descriptionId } = getContext<DialogContext>(DIALOG_CONTEXT);

    const close = () => setOpen(false);

    let hasDescription = $state(false);

    $effect(() => {
        // A description is optional; only point aria-describedby at it when rendered.
        hasDescription = open()
            ? Boolean(document.getElementById(descriptionId))
            : false;
    });
</script>

{#if open()}
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <button
            type="button"
            class="fixed inset-0 bg-black/50"
            tabindex="-1"
            aria-label="Close dialog"
            onclick={close}
        ></button>
        <div
            class={cn(
                'relative z-10 max-h-[calc(100dvh-2rem)] w-full max-w-lg overflow-y-auto rounded-lg border bg-background p-6 shadow-lg',
                className,
            )}
            role="dialog"
            aria-modal="true"
            aria-labelledby={titleId}
            aria-describedby={hasDescription ? descriptionId : undefined}
            use:overlay={{ onEscape: close }}
        >
            {@render children?.()}
        </div>
    </div>
{/if}
