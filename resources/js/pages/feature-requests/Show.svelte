<script lang="ts">
    import { page, useForm, Link, router } from '@inertiajs/svelte';
    import { ArrowLeft, Pencil, Save, Trash2, Calendar, User, Building2, Mail, Phone } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import { Label } from '@/components/ui/label';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { index as featureRequestsIndex, edit as featureRequestsEdit } from '@/routes/feature-requests';

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
        created_at: string;
        updated_at: string;
        user: User;
        division: Division;
    }


    let { featureRequest }: { featureRequest: FeatureRequest } = $props();

    const auth = $derived($page.props.auth);

    const statuses = ['new', 'planning', 'development', 'done', 'released'];

    const form = useForm({
        status: featureRequest.status,
        notes: featureRequest.notes || '',
    });

    function submit() {
        $form.put(`/feature-requests/${featureRequest.id}`, {
            preserveScroll: true,
        });
    }

    function deleteRequest() {
        if (confirm(`Are you sure you want to delete "${featureRequest.title}"?`)) {
            router.delete(`/feature-requests/${featureRequest.id}`);
        }
    }

    function getStatusBadgeColor(status: string): string {
        const colors: Record<string, string> = {
            new: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            planning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            development: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            done: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            released: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    }

    function getPriorityBadgeColor(priority: string): string {
        const colors: Record<string, string> = {
            low: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
            medium: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            high: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            urgent: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        };
        return colors[priority] || 'bg-gray-100 text-gray-800';
    }

    function formatDate(date: string): string {
        return new Date(date).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    }

    function formatDateTime(date: string): string {
        return new Date(date).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function isOverdue(deadline: string): boolean {
        return new Date(deadline) < new Date();
    }
</script>

<AppHead title={featureRequest.title} />

<AppLayout breadcrumbs={[{ title: 'Feature Requests', href: '/feature-requests' }, { title: featureRequest.title }]}>
    <div class="container mx-auto py-8 space-y-6">
        <div class="flex items-center gap-4">
            <Link href={featureRequestsIndex.url()}>
                <Button variant="ghost" size="icon">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div class="flex-1">
                <h1 class="text-3xl font-bold tracking-tight">{featureRequest.title}</h1>
                <p class="text-muted-foreground">
                    Created by {featureRequest.user.name} on {formatDateTime(featureRequest.created_at)}
                </p>
            </div>
            <div class="flex gap-2">
                {#if auth.user}
                    <Link href={featureRequestsEdit.url({ feature_request: featureRequest.id })}>
                        <Button variant="outline">
                            <Pencil class="mr-2 h-4 w-4" />
                            Edit
                        </Button>
                    </Link>
                    <Button variant="destructive" onclick={deleteRequest}>
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                {/if}
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <!-- Main Content -->
            <div class="md:col-span-2 space-y-6">
                <!-- Request Details -->
                <Card>
                    <CardHeader>
                        <CardTitle>Request Details</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label class="text-sm text-muted-foreground">Title</Label>
                                <p class="font-medium">{featureRequest.title}</p>
                            </div>
                            <div>
                                <Label class="text-sm text-muted-foreground">Division</Label>
                                <p class="font-medium flex items-center gap-2">
                                    <Building2 class="h-4 w-4" />
                                    {featureRequest.division.name}
                                </p>
                            </div>
                        </div>

                        {#if featureRequest.description}
                            <div>
                                <Label class="text-sm text-muted-foreground">Description</Label>
                                <p class="mt-1 whitespace-pre-wrap text-sm">{featureRequest.description}</p>
                            </div>
                        {/if}

                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <Label class="text-sm text-muted-foreground">Priority</Label>
                                <div class="mt-1">
                                    <Badge variant="secondary" class={getPriorityBadgeColor(featureRequest.priority)}>
                                        {featureRequest.priority.charAt(0).toUpperCase() + featureRequest.priority.slice(1)}
                                    </Badge>
                                </div>
                            </div>
                            <div>
                                <Label class="text-sm text-muted-foreground">Status</Label>
                                <div class="mt-1">
                                    <Badge variant="secondary" class={getStatusBadgeColor(featureRequest.status)}>
                                        {featureRequest.status.charAt(0).toUpperCase() + featureRequest.status.slice(1)}
                                    </Badge>
                                </div>
                            </div>
                            <div>
                                <Label class="text-sm text-muted-foreground">Deadline</Label>
                                <p class="font-medium flex items-center gap-2 {isOverdue(featureRequest.deadline) && featureRequest.status !== 'released' ? 'text-destructive' : ''}">
                                    <Calendar class="h-4 w-4" />
                                    {formatDate(featureRequest.deadline)}
                                    {#if isOverdue(featureRequest.deadline) && featureRequest.status !== 'released'}
                                        <Badge variant="destructive" class="text-xs">Overdue</Badge>
                                    {/if}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- PIC Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>PIC Information</CardTitle>
                        <CardDescription>Person in charge from the requesting division</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex items-center gap-3">
                            <User class="h-4 w-4 text-muted-foreground" />
                            <span class="font-medium">{featureRequest.requester_name}</span>
                        </div>
                        {#if featureRequest.requester_email}
                            <div class="flex items-center gap-3">
                                <Mail class="h-4 w-4 text-muted-foreground" />
                                <span>{featureRequest.requester_email}</span>
                            </div>
                        {/if}
                        {#if featureRequest.requester_phone}
                            <div class="flex items-center gap-3">
                                <Phone class="h-4 w-4 text-muted-foreground" />
                                <span>{featureRequest.requester_phone}</span>
                            </div>
                        {/if}
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                {#if auth.user}
                <!-- Status Update -->
                <Card>
                    <CardHeader>
                        <CardTitle>Update Status</CardTitle>
                        <CardDescription>Change the status of this request</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onsubmit={(e) => {
                                e.preventDefault();
                                submit();
                            }}
                            class="space-y-4"
                        >
                            <div class="grid gap-2">
                                <Label for="status">Status</Label>
                                <select
                                    id="status"
                                    bind:value={$form.status}
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                >
                                    {#each statuses as status (status)}
                                        <option value={status}>{status.charAt(0).toUpperCase() + status.slice(1)}</option>
                                    {/each}
                                </select>
                            </div>

                            <div class="grid gap-2">
                                <Label for="notes">Notes</Label>
                                <textarea
                                    id="notes"
                                    bind:value={$form.notes}
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Add notes about this request..."
                                ></textarea>
                            </div>

                            <Button type="submit" disabled={$form.processing} class="w-full">
                                <Save class="mr-2 h-4 w-4" />
                                Update Status
                            </Button>
                        </form>
                    </CardContent>
                </Card>
                {/if}

                <!-- Request Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Request Info</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Reporter</span>
                            <span>{featureRequest.user.name}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Created</span>
                            <span>{formatDateTime(featureRequest.created_at)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Updated</span>
                            <span>{formatDateTime(featureRequest.updated_at)}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</AppLayout>
