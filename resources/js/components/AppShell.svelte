<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import { SidebarProvider } from '@/components/ui/sidebar';
    import type { AppShellVariant } from '@/types';

    let {
        variant = 'sidebar',
        class: className = '',
        children,
    }: {
        variant?: AppShellVariant;
        class?: string;
        children?: Snippet;
    } = $props();

    const isOpen = $derived($page.props.sidebarOpen);
</script>

{#if variant === 'header'}
    <div class="flex min-h-svh w-full flex-col {className}">
        <a
            href="#main"
            class="absolute top-[-80px] left-4 z-50 rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground shadow-md focus:top-2"
        >
            Skip to content
        </a>
        {@render children?.()}
    </div>
{:else}
    <SidebarProvider defaultOpen={isOpen}>
        {@render children?.()}
    </SidebarProvider>
{/if}
