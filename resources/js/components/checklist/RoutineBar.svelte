<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import { Lock, MoreHorizontal, Pencil, Plus } from 'lucide-svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogHeader,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { clickOutside } from '@/lib/clickOutside';
    import type {
        ChecklistItem,
        ChecklistProps,
        ChecklistStatus,
    } from '@/types/tasks';

    let { checklist }: { checklist: ChecklistProps } = $props();

    let dialogOpen = $state(false);
    let editing = $state<ChecklistItem | null>(null);
    let menuOpen = $state<number | null>(null);
    let noteOpen = $state(false);
    let noteDraft = $state('');

    const form = useForm({ title: '' });

    let pendingStatus = $state<Record<number, ChecklistStatus>>({});

    const readOnly = $derived(checklist.read_only);
    const hasItems = $derived(checklist.items.length > 0);

    const displayItems = $derived(
        checklist.items.map((item) => ({
            ...item,
            status: pendingStatus[item.id] ?? item.status,
        })),
    );

    const resolved = $derived(
        displayItems.filter(
            (item) => item.status === 'done' || item.status === 'skipped',
        ).length,
    );
    const percent = $derived(
        checklist.stats.total > 0
            ? Math.round((resolved / checklist.stats.total) * 100)
            : 0,
    );

    function statusOf(item: ChecklistItem): ChecklistStatus {
        return pendingStatus[item.id] ?? item.status;
    }

    function toggle(item: ChecklistItem) {
        setStatus(item, statusOf(item) === 'done' ? 'pending' : 'done');
    }

    function setStatus(item: ChecklistItem, status: ChecklistStatus) {
        const previous = pendingStatus[item.id];
        menuOpen = null;
        pendingStatus = { ...pendingStatus, [item.id]: status };

        router.patch(
            `/checklist/${item.id}`,
            { date: checklist.date, status },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    const next = { ...pendingStatus };
                    delete next[item.id];
                    pendingStatus = next;
                },
                onError: () => {
                    const next = { ...pendingStatus };

                    if (previous === undefined) {
                        delete next[item.id];
                    } else {
                        next[item.id] = previous;
                    }

                    pendingStatus = next;
                },
            },
        );
    }

    function move(item: ChecklistItem, direction: 'up' | 'down') {
        menuOpen = null;

        router.patch(
            `/routines/${item.id}/move`,
            { direction },
            { preserveScroll: true, preserveState: true },
        );
    }

    function archive(item: ChecklistItem) {
        menuOpen = null;

        if (!confirm(`Archive "${item.title}"? History stays available.`)) {
            return;
        }

        router.delete(`/routines/${item.id}`, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function openCreate() {
        editing = null;
        $form.reset();
        $form.clearErrors();
        dialogOpen = true;
    }

    function openEdit(item: ChecklistItem) {
        menuOpen = null;
        editing = item;
        $form.title = item.title;
        $form.clearErrors();
        dialogOpen = true;
    }

    function submit() {
        if (editing) {
            $form.patch(`/routines/${editing.id}`, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    dialogOpen = false;
                    editing = null;
                },
            });

            return;
        }

        $form.post('/routines', {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                dialogOpen = false;
                $form.reset();
            },
        });
    }

    function openNote() {
        noteDraft = checklist.note?.body ?? '';
        noteOpen = true;
    }

    function saveNote() {
        router.put(
            '/checklist/note',
            { body: noteDraft },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    noteOpen = false;
                },
            },
        );
    }
</script>

<section
    class="rounded border border-border bg-card"
    aria-label="Daily routine"
>
    <header
        class="flex flex-wrap items-center gap-x-2 gap-y-1 border-b border-border px-3 py-2"
    >
        <span
            class="text-2xs uppercase tracking-[0.14em] text-muted-foreground"
        >
            Routine
        </span>
        <span class="text-base font-semibold tracking-tight">
            {readOnly ? `${checklist.owner.name}'s routine` : 'Your routine'}
        </span>
        <span class="text-xs text-muted-foreground">· today</span>

        {#if readOnly}
            <span
                class="inline-flex items-center gap-1 rounded-sm border border-border px-1.5 py-0.5 text-2xs text-muted-foreground"
            >
                <Lock class="h-2.5 w-2.5" aria-hidden="true" />
                Read-only
            </span>
        {/if}

        <div class="ml-auto flex items-center gap-2" aria-live="polite">
            {#if checklist.stats.streak > 0}
                <span
                    class="rounded-sm border border-violet-500/40 bg-violet-500/10 px-1.5 py-0.5 text-xs text-violet-600 dark:text-violet-300"
                    title={`${checklist.stats.streak}-day streak`}
                >
                    🔥 {checklist.stats.streak}
                </span>
            {/if}
            <span class="text-dense tabular-nums text-muted-foreground">
                {resolved}/{checklist.stats.total} done
            </span>
            <span
                class="h-1 w-20 overflow-hidden rounded bg-muted"
                role="progressbar"
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={percent}
            >
                <span
                    class="block h-full bg-violet-500"
                    style={`width: ${percent}%`}
                ></span>
            </span>
        </div>
    </header>

    {#if hasItems}
        <ul class="grid gap-x-6 px-2 py-1.5 sm:grid-cols-2">
            {#each displayItems as item, index (item.id)}
                <li
                    class="relative flex items-center gap-2 rounded px-1.5 py-1 hover:bg-accent/40"
                >
                    <input
                        id={`routine-${item.id}`}
                        type="checkbox"
                        class="h-3.5 w-3.5 shrink-0 accent-violet-500"
                        checked={item.status === 'done'}
                        disabled={readOnly}
                        onchange={() => toggle(item)}
                    />

                    <label
                        for={`routine-${item.id}`}
                        class="min-w-0 flex-1 cursor-pointer truncate text-sm"
                        class:line-through={item.status === 'done'}
                        class:text-muted-foreground={item.status !== 'pending'}
                        class:cursor-default={readOnly}
                    >
                        {item.title}
                    </label>

                    {#if item.status === 'skipped'}
                        <span
                            class="shrink-0 rounded-sm border border-dashed border-border px-1 text-3xs uppercase tracking-[0.08em] text-muted-foreground"
                        >
                            skipped
                        </span>
                    {/if}

                    {#if !readOnly}
                        <button
                            type="button"
                            data-routine-menu-trigger
                            class="rounded p-0.5 text-muted-foreground opacity-60 hover:bg-accent hover:text-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none group-hover:opacity-100"
                            aria-haspopup="menu"
                            aria-expanded={menuOpen === item.id}
                            aria-label={`Actions for ${item.title}`}
                            onclick={() =>
                                (menuOpen =
                                    menuOpen === item.id ? null : item.id)}
                        >
                            <MoreHorizontal class="h-3.5 w-3.5" />
                        </button>

                        {#if menuOpen === item.id}
                            <div
                                class="absolute top-full right-0 z-20 mt-1 w-36 overflow-hidden rounded border border-border bg-card shadow-lg"
                                role="menu"
                                use:clickOutside={{
                                    handler: () => (menuOpen = null),
                                    ignore: '[data-routine-menu-trigger]',
                                }}
                            >
                                <button
                                    type="button"
                                    role="menuitem"
                                    class="block w-full px-2 py-1.5 text-left text-dense hover:bg-accent"
                                    onclick={() =>
                                        setStatus(
                                            item,
                                            statusOf(item) === 'skipped'
                                                ? 'pending'
                                                : 'skipped',
                                        )}
                                >
                                    {item.status === 'skipped'
                                        ? 'Mark as pending'
                                        : 'Skip'}
                                </button>
                                <button
                                    type="button"
                                    role="menuitem"
                                    disabled={index === 0}
                                    class="block w-full px-2 py-1.5 text-left text-dense hover:bg-accent disabled:opacity-40"
                                    onclick={() => move(item, 'up')}
                                >
                                    Move up
                                </button>
                                <button
                                    type="button"
                                    role="menuitem"
                                    disabled={index ===
                                        checklist.items.length - 1}
                                    class="block w-full px-2 py-1.5 text-left text-dense hover:bg-accent disabled:opacity-40"
                                    onclick={() => move(item, 'down')}
                                >
                                    Move down
                                </button>
                                <button
                                    type="button"
                                    role="menuitem"
                                    class="block w-full px-2 py-1.5 text-left text-dense hover:bg-accent"
                                    onclick={() => openEdit(item)}
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    role="menuitem"
                                    class="block w-full px-2 py-1.5 text-left text-dense text-destructive hover:bg-accent"
                                    onclick={() => archive(item)}
                                >
                                    Archive
                                </button>
                            </div>
                        {/if}
                    {/if}
                </li>
            {/each}
        </ul>
    {:else}
        <p class="px-3 py-2 text-dense text-muted-foreground">
            No routines yet. Add the first thing you do every day.
        </p>
    {/if}

    {#if noteOpen}
        <div class="border-t border-border px-3 py-2">
            <label
                for="routine-note"
                class="text-2xs uppercase tracking-[0.14em] text-muted-foreground"
            >
                Note for today
            </label>
            <textarea
                id="routine-note"
                bind:value={noteDraft}
                rows="2"
                maxlength={2000}
                placeholder="Anything blocking you today?"
                class="mt-1 w-full resize-y rounded border border-border bg-background px-2 py-1.5 text-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
            ></textarea>
            <div class="mt-1 flex justify-end gap-2">
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 px-2 text-dense"
                    onclick={() => (noteOpen = false)}
                >
                    Cancel
                </Button>
                <Button
                    size="sm"
                    class="h-7 px-2 text-dense"
                    onclick={saveNote}
                >
                    Save note
                </Button>
            </div>
        </div>
    {/if}

    <footer
        class="flex flex-wrap items-center gap-2 border-t border-border px-2 py-1"
    >
        {#if !readOnly}
            <Button
                variant="ghost"
                size="sm"
                class="h-7 px-2 text-dense"
                onclick={openCreate}
            >
                <Plus class="h-3 w-3" aria-hidden="true" />
                Add routine
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="h-7 px-2 text-dense"
                onclick={openNote}
            >
                <Pencil class="h-3 w-3" aria-hidden="true" />
                Note
            </Button>
        {/if}

        {#if checklist.note}
            <span
                class="min-w-0 flex-1 truncate text-xs text-muted-foreground italic"
                title={checklist.note.body}
            >
                “{checklist.note.body}”
            </span>
        {:else if !noteOpen}
            <span class="text-xs text-muted-foreground">No note.</span>
        {/if}
    </footer>

    {#if checklist.archived.length > 0}
        <details class="border-t border-border px-3 py-1.5">
            <summary
                class="cursor-pointer text-xs text-muted-foreground select-none"
            >
                Archived routines ({checklist.archived.length})
            </summary>
            <ul class="mt-1 flex flex-col gap-0.5 pb-1">
                {#each checklist.archived as routine (routine.id)}
                    <li
                        class="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <span class="truncate">{routine.title}</span>
                        <span class="ml-auto shrink-0 tabular-nums">
                            {routine.archived_on}
                        </span>
                    </li>
                {/each}
            </ul>
        </details>
    {/if}
</section>

<Dialog bind:open={dialogOpen}>
    <DialogContent class="sm:max-w-[380px]">
        <DialogHeader>
            <DialogTitle>{editing ? 'Edit routine' : 'New routine'}</DialogTitle
            >
            <DialogDescription>
                {editing
                    ? 'Rename this routine. Past entries stay untouched.'
                    : 'Add something you do every day. It starts counting today.'}
            </DialogDescription>
        </DialogHeader>

        <form
            onsubmit={(event) => {
                event.preventDefault();
                submit();
            }}
            class="grid gap-3 py-2"
        >
            <div class="grid gap-2">
                <Label for="routine-title">Routine name</Label>
                <Input
                    id="routine-title"
                    bind:value={$form.title}
                    maxlength={255}
                    placeholder="Review pull request"
                    autocomplete="off"
                />
                {#if $form.errors.title}
                    <span class="text-sm text-destructive"
                        >{$form.errors.title}</span
                    >
                {/if}
            </div>

            <DialogFooter>
                <Button type="submit" disabled={$form.processing}>
                    Save routine
                </Button>
            </DialogFooter>
        </form>
    </DialogContent>
</Dialog>
