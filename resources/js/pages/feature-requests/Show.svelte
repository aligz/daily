<script lang="ts">
    import { page, useForm, Link, router } from '@inertiajs/svelte';
    import {
        ArrowLeft,
        Pencil,
        Save,
        Trash2,
        Calendar,
        User as UserIcon,
        Building2,
        Mail,
        Phone,
    } from 'lucide-svelte';
    import { untrack } from 'svelte';
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
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Textarea } from '@/components/ui/textarea';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { featureStatusBadgeStyles } from '@/lib/board-colors';
    import {
        index as featureRequestsIndex,
        edit as featureRequestsEdit,
    } from '@/routes/feature-requests';

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
        change_reason: string | null;
        proposed_action: string | null;
        effect_on_organization: string | null;
        effect_of_not_approving: string | null;
        requester_name: string;
        requester_email: string | null;
        requester_phone: string | null;
        priority: 'low' | 'medium' | 'high' | 'urgent' | null;
        status: 'new' | 'planning' | 'development' | 'done' | 'released';
        deadline: string | null;
        notes: string | null;
        released_at: string | null;
        created_at: string;
        updated_at: string;
        user: User | null;
        division: Division;
    }

    let {
        featureRequest,
        isPublic,
    }: { featureRequest: FeatureRequest; isPublic: boolean } = $props();

    const auth = $derived($page.props.auth);

    const statuses = ['new', 'planning', 'development', 'done', 'released'];
    const priorities = ['low', 'medium', 'high', 'urgent'];

    const form = useForm(
        untrack(() => ({
            status: featureRequest.status,
            priority: featureRequest.priority ?? '',
            deadline: featureRequest.deadline
                ? featureRequest.deadline.split('T')[0]
                : '',
            notes: featureRequest.notes || '',
        })),
    );

    function submit() {
        $form.put(`/feature-requests/${featureRequest.id}`, {
            preserveScroll: true,
        });
    }

    function deleteRequest() {
        if (
            confirm(
                `Are you sure you want to delete "${featureRequest.title}"?`,
            )
        ) {
            router.delete(`/feature-requests/${featureRequest.id}`);
        }
    }

    const statusBadgeClass = $derived(
        featureStatusBadgeStyles[featureRequest.status] ??
            'bg-muted text-muted-foreground',
    );

    function getPriorityBadgeColor(priority: string): string {
        const colors: Record<string, string> = {
            low: 'bg-muted text-muted-foreground',
            medium: 'bg-info/10 text-info',
            high: 'bg-warning/10 text-warning',
            urgent: 'bg-destructive/10 text-destructive',
        };
        return colors[priority] || 'bg-muted text-muted-foreground';
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

    function isOverdue(deadline: string | null): boolean {
        return Boolean(deadline) && new Date(deadline as string) < new Date();
    }

    function priorityLabel(priority: string | null): string {
        return priority
            ? priority.charAt(0).toUpperCase() + priority.slice(1)
            : 'Not set';
    }
</script>

<AppHead title={featureRequest.title} />

<AppLayout
    breadcrumbs={[
        { title: 'Feature Requests', href: '/feature-requests' },
        { title: featureRequest.title },
    ]}
>
    <div class="container mx-auto py-8 space-y-6">
        <div class="flex items-center gap-4">
            <Link href={featureRequestsIndex.url()}>
                <Button variant="ghost" size="icon">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div class="flex-1">
                <h1 class="text-3xl font-bold tracking-tight">
                    {featureRequest.title}
                </h1>
                <p class="text-muted-foreground">
                    {#if featureRequest.user}
                        Created by {featureRequest.user.name} on {formatDateTime(
                            featureRequest.created_at,
                        )}
                    {:else}
                        Submitted via public form on {formatDateTime(
                            featureRequest.created_at,
                        )}
                    {/if}
                </p>
            </div>
            <div class="flex gap-2">
                {#if auth.user}
                    <Link
                        href={featureRequestsEdit.url({
                            feature_request: featureRequest.id,
                        })}
                    >
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
                                <Label class="text-sm text-muted-foreground"
                                    >Title</Label
                                >
                                <p class="font-medium">
                                    {featureRequest.title}
                                </p>
                            </div>
                            <div>
                                <Label class="text-sm text-muted-foreground"
                                    >Division</Label
                                >
                                <p class="font-medium flex items-center gap-2">
                                    <Building2 class="h-4 w-4" />
                                    {featureRequest.division.name}
                                </p>
                            </div>
                        </div>

                        {#if featureRequest.description}
                            <div>
                                <Label class="text-sm text-muted-foreground"
                                    >Description</Label
                                >
                                <p class="mt-1 whitespace-pre-wrap text-sm">
                                    {featureRequest.description}
                                </p>
                            </div>
                        {/if}

                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <Label class="text-sm text-muted-foreground"
                                    >Priority</Label
                                >
                                <div class="mt-1">
                                    <Badge
                                        variant="secondary"
                                        class={getPriorityBadgeColor(
                                            featureRequest.priority ?? '',
                                        )}
                                    >
                                        {priorityLabel(featureRequest.priority)}
                                    </Badge>
                                </div>
                            </div>
                            <div>
                                <Label class="text-sm text-muted-foreground"
                                    >Status</Label
                                >
                                <div class="mt-1">
                                    <Badge
                                        variant="secondary"
                                        class={statusBadgeClass}
                                    >
                                        {featureRequest.status
                                            .charAt(0)
                                            .toUpperCase() +
                                            featureRequest.status.slice(1)}
                                    </Badge>
                                </div>
                            </div>
                            <div>
                                <Label class="text-sm text-muted-foreground"
                                    >Deadline</Label
                                >
                                {#if featureRequest.deadline}
                                    <p
                                        class="font-medium flex items-center gap-2 {isOverdue(
                                            featureRequest.deadline,
                                        ) &&
                                        featureRequest.status !== 'released'
                                            ? 'text-destructive'
                                            : ''}"
                                    >
                                        <Calendar class="h-4 w-4" />
                                        {formatDate(featureRequest.deadline)}
                                        {#if isOverdue(featureRequest.deadline) && featureRequest.status !== 'released'}
                                            <Badge
                                                variant="destructive"
                                                class="text-xs">Overdue</Badge
                                            >
                                        {/if}
                                    </p>
                                {:else}
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        Not set yet
                                    </p>
                                {/if}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Change Request -->
                {#if featureRequest.change_reason || featureRequest.proposed_action || featureRequest.effect_on_organization || featureRequest.effect_of_not_approving}
                    <Card>
                        <CardHeader>
                            <CardTitle>Change Request</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-sm text-muted-foreground"
                                    >Change reason</Label
                                >
                                <p class="mt-1 whitespace-pre-wrap text-sm">
                                    {featureRequest.change_reason ?? '—'}
                                </p>
                            </div>
                            <div>
                                <Label class="text-sm text-muted-foreground"
                                    >Proposed action</Label
                                >
                                <p class="mt-1 whitespace-pre-wrap text-sm">
                                    {featureRequest.proposed_action ?? '—'}
                                </p>
                            </div>
                            <div>
                                <Label class="text-sm text-muted-foreground"
                                    >Effect on the organization</Label
                                >
                                <p class="mt-1 whitespace-pre-wrap text-sm">
                                    {featureRequest.effect_on_organization ??
                                        '—'}
                                </p>
                            </div>
                            <div>
                                <Label class="text-sm text-muted-foreground"
                                    >Effect of NOT approving this change</Label
                                >
                                <p class="mt-1 whitespace-pre-wrap text-sm">
                                    {featureRequest.effect_of_not_approving ??
                                        '—'}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                {/if}

                <!-- PIC Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>PIC Information</CardTitle>
                        <CardDescription
                            >Person in charge from the requesting division</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex items-center gap-3">
                            <UserIcon class="h-4 w-4 text-muted-foreground" />
                            <span class="font-medium"
                                >{featureRequest.requester_name}</span
                            >
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
                    <!-- Triage -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Triage</CardTitle>
                            <CardDescription
                                >Set priority and deadline, then move the
                                status.</CardDescription
                            >
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
                                            <option value={status}
                                                >{status
                                                    .charAt(0)
                                                    .toUpperCase() +
                                                    status.slice(1)}</option
                                            >
                                        {/each}
                                    </select>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="priority">Priority</Label>
                                    <select
                                        id="priority"
                                        bind:value={$form.priority}
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    >
                                        <option value="">Not set</option>
                                        {#each priorities as priority (priority)}
                                            <option value={priority}
                                                >{priority
                                                    .charAt(0)
                                                    .toUpperCase() +
                                                    priority.slice(1)}</option
                                            >
                                        {/each}
                                    </select>
                                    {#if $form.errors.priority}
                                        <span class="text-sm text-destructive"
                                            >{$form.errors.priority}</span
                                        >
                                    {/if}
                                </div>

                                <div class="grid gap-2">
                                    <Label for="deadline">Deadline</Label>
                                    <Input
                                        id="deadline"
                                        type="date"
                                        bind:value={$form.deadline}
                                    />
                                    {#if $form.errors.deadline}
                                        <span class="text-sm text-destructive"
                                            >{$form.errors.deadline}</span
                                        >
                                    {/if}
                                </div>

                                <div class="grid gap-2">
                                    <Label for="notes">Notes</Label>
                                    <Textarea
                                        id="notes"
                                        bind:value={$form.notes}
                                        rows={3}
                                        placeholder="Add notes about this request..."
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={$form.processing}
                                    class="w-full"
                                >
                                    <Save class="mr-2 h-4 w-4" />
                                    Save triage
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
                            <span
                                >{isPublic
                                    ? 'Public form'
                                    : (featureRequest.user?.name ?? '—')}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Created</span>
                            <span
                                >{formatDateTime(
                                    featureRequest.created_at,
                                )}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Updated</span>
                            <span
                                >{formatDateTime(
                                    featureRequest.updated_at,
                                )}</span
                            >
                        </div>
                        {#if featureRequest.released_at}
                            <div class="flex justify-between">
                                <span class="text-muted-foreground"
                                    >Released</span
                                >
                                <span
                                    >{formatDateTime(
                                        featureRequest.released_at,
                                    )}</span
                                >
                            </div>
                        {/if}
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</AppLayout>
