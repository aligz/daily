<script lang="ts">
    import { getContext } from 'svelte';
    import { DIALOG_CONTEXT, type DialogContext } from './context';

    const { descriptionId } = getContext<DialogContext>(DIALOG_CONTEXT);
</script>

<p id={descriptionId} class="text-sm text-muted-foreground">
    <slot />
</p>
