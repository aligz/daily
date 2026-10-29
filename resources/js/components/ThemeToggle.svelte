<script lang="ts">
    import Moon from 'lucide-svelte/icons/moon';
    import Sun from 'lucide-svelte/icons/sun';
    import { Button } from '@/components/ui/button';
    import { updateAppearance } from '@/lib/theme.svelte';

    function toggle(): void {
        const isDark = document.documentElement.classList.contains('dark');
        updateAppearance(isDark ? 'light' : 'dark');
    }
</script>

<Button
    variant="ghost"
    size="icon"
    onclick={toggle}
    aria-label="Toggle dark mode"
    title="Toggle dark mode"
>
    <Moon class="h-4 w-4 dark:hidden" aria-hidden="true" />
    <Sun class="hidden h-4 w-4 dark:block" aria-hidden="true" />
</Button>
