<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import { Plus, Building2, Pencil, Trash2 } from 'lucide-svelte';
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
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogHeader,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import AppLayout from '@/layouts/AppLayout.svelte';

    interface Division {
        id: number;
        name: string;
        feature_requests_count: number;
        created_at: string;
    }

    let { divisions }: { divisions: Division[] } = $props();

    let isOpen = $state(false);
    let editingDivision = $state<Division | null>(null);

    const form = useForm({
        name: '',
    });

    function openDialog(division?: Division) {
        if (division) {
            editingDivision = division;
            $form.name = division.name;
        } else {
            editingDivision = null;
            $form.reset();
        }
        isOpen = true;
    }

    function submit() {
        if (editingDivision) {
            $form.put(`/divisions/${editingDivision.id}`, {
                onSuccess: () => {
                    isOpen = false;
                    editingDivision = null;
                    $form.reset();
                },
            });
        } else {
            $form.post('/divisions', {
                onSuccess: () => {
                    isOpen = false;
                    $form.reset();
                },
            });
        }
    }

    function deleteDivision(division: Division) {
        if (confirm(`Are you sure you want to delete "${division.name}"?`)) {
            router.delete(`/divisions/${division.id}`);
        }
    }
</script>

<AppHead title="Divisions" />

<AppLayout breadcrumbs={[{ title: 'Divisions', href: '/divisions' }]}>
    <div class="container mx-auto py-8 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Divisions</h1>
                <p class="text-muted-foreground">
                    Manage organizational divisions
                </p>
            </div>
            <Dialog bind:open={isOpen}>
                <DialogTrigger asChild>
                    {#snippet children(params)}
                        <Button {...params} onclick={() => openDialog()}>
                            <Plus class="mr-2 h-4 w-4" />
                            Add Division
                        </Button>
                    {/snippet}
                </DialogTrigger>
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle
                            >{editingDivision
                                ? 'Edit Division'
                                : 'Add Division'}</DialogTitle
                        >
                        <DialogDescription>
                            {editingDivision
                                ? 'Update the division name.'
                                : 'Add a new division to the system.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        onsubmit={(e) => {
                            e.preventDefault();
                            submit();
                        }}
                        class="grid gap-4 py-4"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                bind:value={$form.name}
                                placeholder="Division name"
                                autocomplete="off"
                            />
                            {#if $form.errors.name}
                                <span class="text-sm text-destructive"
                                    >{$form.errors.name}</span
                                >
                            {/if}
                        </div>
                        <DialogFooter>
                            <Button
                                type="submit"
                                disabled={$form.processing}
                                >{editingDivision ? 'Update' : 'Save'}</Button
                            >
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Building2 class="h-5 w-5" />
                    All Divisions
                </CardTitle>
                <CardDescription>
                    {divisions.length} division(s) in the system
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="w-full overflow-x-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="[&_tr]:border-b">
                            <tr class="border-b transition-colors hover:bg-muted/50">
                                <th class="h-12 px-4 text-left align-middle font-medium">Name</th>
                                <th class="h-12 px-4 text-center align-middle font-medium">Feature Requests</th>
                                <th class="h-12 px-4 text-right align-middle font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            {#each divisions as division (division.id)}
                                <tr class="border-b transition-colors hover:bg-muted/50">
                                    <td class="p-4 font-medium">{division.name}</td>
                                    <td class="p-4 text-center">
                                        <Badge variant="secondary">
                                            {division.feature_requests_count} request(s)
                                        </Badge>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onclick={() => openDialog(division)}
                                            >
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onclick={() => deleteDivision(division)}
                                            >
                                                <Trash2 class="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>

                {#if divisions.length === 0}
                    <div class="py-12 text-center text-muted-foreground">
                        No divisions found. Add your first division to get started.
                    </div>
                {/if}
            </CardContent>
        </Card>
    </div>
</AppLayout>
