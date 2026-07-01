<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import { Plus, Calendar, Building2, User } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import { Input } from '@/components/ui/input';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { create as featureRequestsCreate, show as featureRequestsShow, update as featureRequestsUpdate } from '@/routes/feature-requests';

    interface User {
        id: number;
        name: string;
        email: string;
    }

    interface Division {
        id: number;
        name: string;
    }

    interface FeatureRequest {
        id: number;
        title: string;
        description: string | null;
        requester_name: string;
        requester_email: string | null;
        requester_phone: string | null;
        priority: 'low' | 'medium' | 'high' | 'urgent';
        status: 'new' | 'planning' | 'development' | 'done' | 'released';
        deadline: string;
        notes: string | null;
        released_at: string | null;
        created_at: string;
        user: User;
        division: Division;
    }

    let { featureRequests, divisions }: { featureRequests: FeatureRequest[]; divisions: Division[] } = $props();

    const auth = $derived($page.props.auth);

    let searchQuery = $state('');
    let selectedDivisionId = $state<number | null>(null);
    let selectedPriority = $state<string | null>(null);

    const boards = ['new', 'planning', 'development', 'done', 'released'];
    const priorities = ['low', 'medium', 'high', 'urgent'];

    let filteredRequests = $derived.by(() => {
        let result = featureRequests;

        if (searchQuery) {
            const query = searchQuery.toLowerCase();
            result = result.filter(
                (r) =>
                    r.title.toLowerCase().includes(query) ||
                    r.requester_name.toLowerCase().includes(query) ||
                    r.division.name.toLowerCase().includes(query),
            );
        }

        if (selectedDivisionId) {
            result = result.filter((r) => r.division.id === selectedDivisionId);
        }

        if (selectedPriority) {
            result = result.filter((r) => r.priority === selectedPriority);
        }

        return result;
    });

    let kanbanData = $derived.by(() => {
        const data: Record<string, FeatureRequest[]> = {};
        for (const board of boards) {
            data[board] = filteredRequests
                .filter((r) => r.status === board)
                .sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());
        }
        return data;
    });

    function getPriorityBadgeColor(priority: string): string {
        const colors: Record<string, string> = {
            low: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
            medium: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            high: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            urgent: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        };
        return colors[priority] || 'bg-gray-100 text-gray-800';
    }

    function getBoardHeaderStyle(board: string): string {
        const styles: Record<string, string> = {
            new: 'bg-blue-100/50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800 text-blue-800 dark:text-blue-200',
            planning: 'bg-yellow-100/50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200',
            development: 'bg-purple-100/50 border-purple-200 dark:bg-purple-900/20 dark:border-purple-800 text-purple-800 dark:text-purple-200',
            done: 'bg-orange-100/50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-800 text-orange-800 dark:text-orange-200',
            released: 'bg-green-100/50 border-green-200 dark:bg-green-900/20 dark:border-green-800 text-green-800 dark:text-green-200',
        };
        return styles[board] || 'bg-muted/50';
    }

    const userColors = [
        'bg-red-500 text-white',
        'bg-orange-500 text-white',
        'bg-amber-500 text-white',
        'bg-yellow-500 text-black',
        'bg-lime-500 text-black',
        'bg-green-500 text-white',
        'bg-emerald-500 text-white',
        'bg-teal-500 text-white',
        'bg-cyan-500 text-black',
        'bg-sky-500 text-white',
        'bg-blue-500 text-white',
        'bg-indigo-500 text-white',
        'bg-violet-500 text-white',
        'bg-purple-500 text-white',
        'bg-fuchsia-500 text-white',
        'bg-pink-500 text-white',
        'bg-rose-500 text-white',
    ];

    function getUserColor(name: string): string {
        let hash = 0;
        for (let i = 0; i < name.length; i++) {
            hash = name.charCodeAt(i) + ((hash << 5) - hash);
        }
        const index = Math.abs(hash) % userColors.length;
        return userColors[index];
    }

    function getInitials(name: string): string {
        return name
            .split(' ')
            .map((n) => n[0])
            .slice(0, 2)
            .join('')
            .toUpperCase();
    }

    function isOverdue(deadline: string, status: string): boolean {
        if (status === 'released') return false;
        return new Date(deadline) < new Date();
    }

    function formatDate(date: string): string {
        return new Date(date).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    }

    function goToDetail(request: FeatureRequest) {
        router.visit(featureRequestsShow.url({ feature_request: request.id }));
    }

    // Drag and Drop
    let draggedRequest = $state<FeatureRequest | null>(null);

    function handleDragStart(event: DragEvent, request: FeatureRequest) {
        draggedRequest = request;
        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.dropEffect = 'move';
            event.dataTransfer.setData('text/plain', JSON.stringify(request));
        }
    }

    function handleDragOver(event: DragEvent) {
        event.preventDefault();
        if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
    }

    function handleDrop(event: DragEvent, newStatus: string) {
        event.preventDefault();
        if (draggedRequest && draggedRequest.status !== newStatus) {
            router.put(
                featureRequestsUpdate.url({ feature_request: draggedRequest.id }),
                { status: newStatus },
                { preserveScroll: true }
            );
        }
        draggedRequest = null;
    }
</script>

<AppHead title="Feature Requests" />

<AppLayout breadcrumbs={[{ title: 'Feature Requests', href: '/feature-requests' }]}>
    <div class="flex h-full flex-col p-4">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Feature Requests</h1>

            <div class="flex gap-4">
                {#if auth.user}
                    <a href={featureRequestsCreate.url()}>
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            New Request
                        </Button>
                    </a>
                {/if}
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-4 flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px] max-w-xs">
                <label for="search" class="text-sm font-medium mb-1 block">Search</label>
                <Input
                    id="search"
                    bind:value={searchQuery}
                    placeholder="Search title, requester, division..."
                />
            </div>
            <div class="min-w-[150px]">
                <label for="division" class="text-sm font-medium mb-1 block">Division</label>
                <select
                    id="division"
                    bind:value={selectedDivisionId}
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <option value={null}>All Divisions</option>
                    {#each divisions as division (division.id)}
                        <option value={division.id}>{division.name}</option>
                    {/each}
                </select>
            </div>
            <div class="min-w-[150px]">
                <label for="priority" class="text-sm font-medium mb-1 block">Priority</label>
                <select
                    id="priority"
                    bind:value={selectedPriority}
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <option value={null}>All Priorities</option>
                    {#each priorities as priority (priority)}
                        <option value={priority}>{priority.charAt(0).toUpperCase() + priority.slice(1)}</option>
                    {/each}
                </select>
            </div>
            <div class="text-sm text-muted-foreground pb-2">
                {filteredRequests.length} of {featureRequests.length} request(s)
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="flex flex-1 min-h-0 gap-4 overflow-x-auto pb-4">
            {#each boards as board (board)}
                <div
                    class="flex min-h-0 flex-1 min-w-[280px] max-w-[350px] flex-col rounded border border-muted/50 p-3 bg-muted/30"
                    role="region"
                    aria-label="{board} column"
                    ondrop={auth.user ? (e) => handleDrop(e, board) : undefined}
                    ondragover={auth.user ? handleDragOver : undefined}
                >
                    <div class="mb-3 flex items-center justify-between px-3 py-2 rounded border {getBoardHeaderStyle(board)}">
                        <h3 class="font-semibold capitalize text-sm">
                            {board}
                        </h3>
                        <span class="rounded-full bg-background px-2 py-0.5 text-xs font-medium text-muted-foreground border">
                            {kanbanData[board]?.length || 0}
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col gap-2 overflow-y-auto pr-1">
                        {#each kanbanData[board] || [] as request (request.id)}
                            <div
                                role="button"
                                tabindex="0"
                                class={auth.user ? "cursor-grab active:cursor-grabbing" : "cursor-pointer"}
                                draggable={auth.user ? "true" : "false"}
                                ondragstart={auth.user ? (e) => handleDragStart(e, request) : undefined}
                                onclick={() => goToDetail(request)}
                                onkeydown={(e) => e.key === 'Enter' && goToDetail(request)}
                            >
                                <Card class="shadow-none hover:shadow-md transition-shadow py-2 gap-1 border-transparent">
                                    <CardHeader class="p-2 pb-0 space-y-0">
                                        <div class="flex items-start justify-between gap-2">
                                            <CardTitle class="text-sm font-medium leading-snug line-clamp-2">
                                                {request.title}
                                            </CardTitle>
                                            <Badge variant="secondary" class="shrink-0 text-[10px] {getPriorityBadgeColor(request.priority)}">
                                                {request.priority.charAt(0).toUpperCase() + request.priority.slice(1)}
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent class="p-2 pt-1 space-y-2">
                                        <!-- Requester & Division -->
                                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <User class="h-3 w-3 shrink-0" />
                                            <span class="truncate">{request.requester_name}</span>
                                            <span class="text-border">|</span>
                                            <Building2 class="h-3 w-3 shrink-0" />
                                            <span class="truncate">{request.division.name}</span>
                                        </div>

                                        <!-- Deadline -->

                                        {#if request.status === "released" && request.released_at}
                                            <div class="flex items-center gap-1.5 text-xs text-green-600 dark:text-green-400">
                                                <svg class="h-3 w-3 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                                                <span>Released {formatDate(request.released_at)}</span>
                                            </div>
                                        {/if}
                                        <div class="flex items-center gap-1.5 text-xs {isOverdue(request.deadline, request.status) ? 'text-destructive font-medium' : 'text-muted-foreground'}">
                                            <Calendar class="h-3 w-3 shrink-0" />
                                            <span>{formatDate(request.deadline)}</span>
                                            {#if isOverdue(request.deadline, request.status)}
                                                <Badge variant="destructive" class="text-[9px] px-1 py-0">Overdue</Badge>
                                            {/if}
                                        </div>

                                        <!-- Reporter -->
                                        <div class="flex items-center gap-1.5 pt-1 border-t border-border/50">
                                            <Avatar class="h-4 w-4">
                                                <AvatarFallback class="text-[8px] {getUserColor(request.user.name)}">
                                                    {getInitials(request.user.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <span class="text-[10px] text-muted-foreground">{request.user.name}</span>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        {/each}

                        {#if (kanbanData[board]?.length || 0) === 0}
                            <div class="flex h-20 flex-col items-center justify-center rounded-lg border border-dashed text-xs text-muted-foreground">
                                No requests
                            </div>
                        {/if}
                    </div>
                </div>
            {/each}
        </div>
    </div>
</AppLayout>
