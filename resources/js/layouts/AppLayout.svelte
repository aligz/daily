<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppLayout from '@/layouts/app/AppHeaderKanbanLayout.svelte';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs: _breadcrumbs = [],
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        children?: Snippet;
    } = $props();
</script>

<AppLayout>
    {@render children?.()}
</AppLayout>
