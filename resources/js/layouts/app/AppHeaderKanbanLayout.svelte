<script lang="ts">
    import type { Snippet } from 'svelte';
    import { Link, page } from '@inertiajs/svelte';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import BarChart3 from 'lucide-svelte/icons/bar-chart-3';
    import AppLogo from '@/components/AppLogo.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import AppContent from '@/components/AppContent.svelte';
    import AppShell from '@/components/AppShell.svelte';
    import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import UserMenuContent from '@/components/UserMenuContent.svelte';
    import { getInitials } from '@/lib/initials';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import type { BreadcrumbItem, NavItem } from '@/types';
    import { currentUrlState } from '@/lib/currentUrl';

    let {
        breadcrumbs = [],
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        children?: Snippet;
    } = $props();

    const auth = $derived($page.props.auth);
    const { currentUrl, isCurrentUrl, whenCurrentUrl } = currentUrlState();

    const activeItemStyles = 'bg-accent text-accent-foreground';

    const mainNavItems: NavItem[] = [
        {
            title: 'Tasks',
            href: '/tasks',
            icon: LayoutGrid,
        },
        {
            title: 'Reports',
            href: '/reports',
            icon: BarChart3,
        },
    ];
</script>

<AppShell variant="header" class="flex-col">
    <!-- Header -->
    <div class="border-b border-border/80">
        <div class="flex h-16 items-center px-4 md:px-6">
            <Link href={toUrl(dashboard())} class="flex items-center gap-x-2">
                <AppLogo />
            </Link>

            <!-- Navigation -->
            <nav class="ml-6 flex h-full items-center space-x-1">
                {#each mainNavItems as item (item.href)}
                    <Link
                        href={toUrl(item.href)}
                        class="flex items-center gap-x-2 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground {whenCurrentUrl(item.href, $currentUrl, activeItemStyles, '') ?? ''}"
                    >
                        {#if item.icon}
                            <item.icon class="h-4 w-4" />
                        {/if}
                        {item.title}
                    </Link>
                {/each}
            </nav>

            <div class="ml-auto flex items-center space-x-2">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        {#snippet children(props)}
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                                onclick={props.onclick}
                                aria-expanded={props['aria-expanded']}
                                data-state={props['data-state']}
                            >
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    {#if auth.user.avatar}
                                        <AvatarImage src={auth.user.avatar} alt={auth.user.name} />
                                    {/if}
                                    <AvatarFallback class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                        {getInitials(auth.user?.name)}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        {/snippet}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </div>

    <!-- Content -->
    <AppContent variant="header" class="overflow-x-hidden">
        {@render children?.()}
    </AppContent>
</AppShell>
