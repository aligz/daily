<script lang="ts">
    import type { Snippet } from 'svelte';
    import { SidebarInset } from '@/components/ui/sidebar';
    import { cn } from '@/lib/utils';

    let {
        variant = 'sidebar',
        class: className = '',
        children,
    }: {
        variant?: 'header' | 'sidebar';
        class?: string;
        children?: Snippet;
    } = $props();
</script>

{#if variant === 'sidebar'}
    <SidebarInset class={className}>
        {@render children?.()}
    </SidebarInset>
{:else}
    <main
        id="main"
        tabindex="-1"
        class={cn('flex h-full w-full flex-1 flex-col gap-4', className)}
    >
        {@render children?.()}
    </main>
{/if}
