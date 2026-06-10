<script lang="ts">
    import { useForm, Link } from '@inertiajs/svelte';
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
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { index as featureRequestsIndex } from '@/routes/feature-requests';

    interface Division {
        id: number;
        name: string;
    }

    let { divisions }: { divisions: Division[] } = $props();

    const priorities = ['low', 'medium', 'high', 'urgent'];

    const form = useForm({
        division_id: '',
        requester_name: '',
        requester_email: '',
        requester_phone: '',
        title: '',
        description: '',
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

<AppLayout breadcrumbs={[{ title: 'Feature Requests', href: '/feature-requests' }, { title: 'New Request' }]}>
    <div class="container mx-auto py-8 space-y-6">
        <div class="flex items-center gap-4">
            <Link href={featureRequestsIndex.url()}>
                <Button variant="ghost" size="icon">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-3xl font-bold tracking-tight">New Feature Request</h1>
                <p class="text-muted-foreground">
                    Submit a new feature request from a division
                </p>
            </div>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Request Details</CardTitle>
                <CardDescription>
                    Fill in the details for the feature request. Fields marked with * are required.
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
                                <option value={division.id}>{division.name}</option>
                            {/each}
                        </select>
                        {#if $form.errors.division_id}
                            <span class="text-sm text-destructive">{$form.errors.division_id}</span>
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
                                <span class="text-sm text-destructive">{$form.errors.requester_name}</span>
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
                                <span class="text-sm text-destructive">{$form.errors.requester_email}</span>
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
                            <span class="text-sm text-destructive">{$form.errors.requester_phone}</span>
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
                            <span class="text-sm text-destructive">{$form.errors.title}</span>
                        {/if}
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            bind:value={$form.description}
                            class="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Describe the feature request in detail..."
                        ></textarea>
                        {#if $form.errors.description}
                            <span class="text-sm text-destructive">{$form.errors.description}</span>
                        {/if}
                    </div>

                    <!-- Priority & Deadline -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="priority">Priority *</Label>
                            <select
                                id="priority"
                                bind:value={$form.priority}
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                {#each priorities as priority (priority)}
                                    <option value={priority}>{priority.charAt(0).toUpperCase() + priority.slice(1)}</option>
                                {/each}
                            </select>
                            {#if $form.errors.priority}
                                <span class="text-sm text-destructive">{$form.errors.priority}</span>
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
                                <span class="text-sm text-destructive">{$form.errors.deadline}</span>
                            {/if}
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 mt-4">
                        <Link href={featureRequestsIndex.url()}>
                            <Button variant="outline" type="button">Cancel</Button>
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
