<script lang="ts">
    import { getContext } from 'svelte';
    import { DIALOG_CONTEXT, type DialogContext } from './context';

    const { titleId } = getContext<DialogContext>(DIALOG_CONTEXT);
</script>

<h2 id={titleId} class="text-lg font-semibold leading-none tracking-tight">
    <slot />
</h2>
