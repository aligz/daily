<script lang="ts">
    import type { Snippet } from 'svelte';
    import { setContext } from 'svelte';
    import { DIALOG_CONTEXT, nextDialogId, type DialogContext } from './context';

    let { open = $bindable(false), children }: { open?: boolean; children?: Snippet } = $props();

    const id = nextDialogId();

    const context: DialogContext = {
        open: () => open,
        setOpen: (value: boolean) => {
            open = value;
        },
        titleId: `dialog-title-${id}`,
        descriptionId: `dialog-description-${id}`,
    };

    setContext(DIALOG_CONTEXT, context);
</script>

{@render children?.()}
