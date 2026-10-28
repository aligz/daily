<script lang="ts">
    import { page, useForm, Link } from '@inertiajs/svelte';
    import { ArrowLeft, Send } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
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
    import { index as featureRequestsIndex } from '@/routes/feature-requests';

    interface Division {
        id: number;
        name: string;
    }

    let { divisions }: { divisions: Division[] } = $props();

    const auth = $derived($page.props.auth);

    const priorities = ['low', 'medium', 'high', 'urgent'];

    const form = useForm({
        division_id: '',
        requester_name: '',
        requester_email: '',
        requester_phone: '',
        title: '',
        description: '',
        change_reason: '',
        proposed_action: '',
        effect_on_organization: '',
        effect_of_not_approving: '',
        priority: 'medium',
        deadline: '',
    });

    function submit() {
        $form.post('/feature-requests');
    }

    function getTodayDate(): string {
        return new Date().toISOString().split('T')[0];
    }
</script>

<AppHead title="New Feature Request" />

<AppLayout
    breadcrumbs={[
        { title: 'Feature Requests', href: '/feature-requests' },
        { title: 'New Request' },
    ]}
>
    <div class="container mx-auto space-y-6 px-4 py-8">
        <div class="flex items-center gap-4">
            <Link href={featureRequestsIndex.url()}>
                <Button variant="ghost" size="icon" aria-label="Back to board">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-3xl font-bold tracking-tight">
                    New Feature Request
                </h1>
                <p class="text-muted-foreground">
                    Submit a change request from a division
                </p>
            </div>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Request Details</CardTitle>
                <CardDescription>
                    Fill in the details below. Fields marked with * are
                    required.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form
                    onsubmit={(e) => {
                        e.preventDefault();
                        submit();
                    }}
                    class="grid gap-6"
                >
                    <!-- Division -->
                    <div class="grid gap-2">
                        <Label for="division_id">Division *</Label>
                        <select
                            id="division_id"
                            bind:value={$form.division_id}
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">Select a division</option>
                            {#each divisions as division (division.id)}
                                <option value={division.id}
                                    >{division.name}</option
                                >
                            {/each}
                        </select>
                        {#if $form.errors.division_id}
                            <span class="text-sm text-destructive"
                                >{$form.errors.division_id}</span
                            >
                        {/if}
                    </div>

                    <!-- PIC Information -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="requester_name">PIC Name *</Label>
                            <Input
                                id="requester_name"
                                bind:value={$form.requester_name}
                                placeholder="Name of the person requesting"
                                autocomplete="off"
                            />
                            {#if $form.errors.requester_name}
                                <span class="text-sm text-destructive"
                                    >{$form.errors.requester_name}</span
                                >
                            {/if}
                        </div>
                        <div class="grid gap-2">
                            <Label for="requester_email">PIC Email</Label>
                            <Input
                                id="requester_email"
                                type="email"
                                bind:value={$form.requester_email}
                                placeholder="email@company.com"
                                autocomplete="off"
                            />
                            {#if $form.errors.requester_email}
                                <span class="text-sm text-destructive"
                                    >{$form.errors.requester_email}</span
                                >
                            {/if}
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="requester_phone">PIC Phone</Label>
                        <Input
                            id="requester_phone"
                            bind:value={$form.requester_phone}
                            placeholder="08xxxxxxxxxx"
                            autocomplete="off"
                        />
                        {#if $form.errors.requester_phone}
                            <span class="text-sm text-destructive"
                                >{$form.errors.requester_phone}</span
                            >
                        {/if}
                    </div>

                    <!-- Request Information -->
                    <div class="grid gap-2">
                        <Label for="title">Title *</Label>
                        <Input
                            id="title"
                            bind:value={$form.title}
                            placeholder="Feature request title"
                            autocomplete="off"
                        />
                        {#if $form.errors.title}
                            <span class="text-sm text-destructive"
                                >{$form.errors.title}</span
                            >
                        {/if}
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description *</Label>
                        <Textarea
                            id="description"
                            bind:value={$form.description}
                            rows={4}
                            placeholder="Describe the feature request in detail..."
                        />
                        {#if $form.errors.description}
                            <span class="text-sm text-destructive"
                                >{$form.errors.description}</span
                            >
                        {/if}
                    </div>

                    <!-- Change -->
                    <h2 class="text-dense font-semibold">Change</h2>

                    <div class="grid gap-2">
                        <Label for="change_reason">Change reason *</Label>
                        <Textarea
                            id="change_reason"
                            bind:value={$form.change_reason}
                            rows={3}
                            placeholder="Why does this change need to happen?"
                        />
                        {#if $form.errors.change_reason}
                            <span class="text-sm text-destructive"
                                >{$form.errors.change_reason}</span
                            >
                        {/if}
                    </div>

                    <div class="grid gap-2">
                        <Label for="proposed_action">Proposed action *</Label>
                        <Textarea
                            id="proposed_action"
                            bind:value={$form.proposed_action}
                            rows={3}
                            placeholder="What should be done?"
                        />
                        {#if $form.errors.proposed_action}
                            <span class="text-sm text-destructive"
                                >{$form.errors.proposed_action}</span
                            >
                        {/if}
                    </div>

                    <!-- Impact -->
                    <h2 class="text-dense font-semibold">Impact</h2>

                    <div class="grid gap-2">
                        <Label for="effect_on_organization"
                            >Effect on the organization *</Label
                        >
                        <Textarea
                            id="effect_on_organization"
                            bind:value={$form.effect_on_organization}
                            rows={3}
                            placeholder="Who is affected, and how?"
                        />
                        {#if $form.errors.effect_on_organization}
                            <span class="text-sm text-destructive"
                                >{$form.errors.effect_on_organization}</span
                            >
                        {/if}
                    </div>

                    <div class="grid gap-2">
                        <Label for="effect_of_not_approving"
                            >Effect of NOT approving this change *</Label
                        >
                        <Textarea
                            id="effect_of_not_approving"
                            bind:value={$form.effect_of_not_approving}
                            rows={3}
                            placeholder="What happens if this is not approved?"
                        />
                        {#if $form.errors.effect_of_not_approving}
                            <span class="text-sm text-destructive"
                                >{$form.errors.effect_of_not_approving}</span
                            >
                        {/if}
                    </div>

                    <!-- Priority & deadline belong to the team, not the requester -->
                    {#if auth.user}
                        <h2 class="text-dense font-semibold">Triage</h2>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="priority">Priority *</Label>
                                <select
                                    id="priority"
                                    bind:value={$form.priority}
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                >
                                    {#each priorities as priority (priority)}
                                        <option value={priority}
                                            >{priority.charAt(0).toUpperCase() +
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
                                <Label for="deadline">Deadline *</Label>
                                <Input
                                    id="deadline"
                                    type="date"
                                    bind:value={$form.deadline}
                                    min={getTodayDate()}
                                />
                                {#if $form.errors.deadline}
                                    <span class="text-sm text-destructive"
                                        >{$form.errors.deadline}</span
                                    >
                                {/if}
                            </div>
                        </div>
                    {:else}
                        <p class="text-sm text-muted-foreground">
                            Priority and deadline are set by the team after
                            review.
                        </p>
                    {/if}

                    <div class="mt-4 flex justify-end gap-4">
                        <Link href={featureRequestsIndex.url()}>
                            <Button variant="outline" type="button"
                                >Cancel</Button
                            >
                        </Link>
                        <Button type="submit" disabled={$form.processing}>
                            <Send class="mr-2 h-4 w-4" />
                            Submit Request
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</AppLayout>
