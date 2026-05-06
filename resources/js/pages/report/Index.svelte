<script lang="ts">
    import AppLayout from '@/layouts/AppLayout.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { router } from '@inertiajs/svelte';
    import { Badge } from '@/components/ui/badge';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { Clock, CheckCircle, Timer, BarChart3 } from 'lucide-svelte';

    interface UserReport {
        user: {
            id: number;
            name: string;
            email: string;
            avatar: string | null;
        };
        total_tasks: number;
        completed_tasks: number;
        avg_completion_time_hours: number | null;
        status_breakdown: {
            backlog: number;
            todo: number;
            today: number;
            done: number;
        };
        completion_times: number[];
    }

    let { userReports, dateRange }: { userReports: UserReport[]; dateRange: number } = $props();

    const dateRanges = [7, 14, 30, 60, 90];

    function changeDateRange(days: number) {
        router.get(`/reports?date_range=${days}`, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    }

    function formatHours(hours: number | null): string {
        if (hours === null) return '-';
        if (hours < 1) {
            return `${Math.round(hours * 60)}m`;
        }
        if (hours < 24) {
            return `${hours.toFixed(1)}h`;
        }
        const days = Math.floor(hours / 24);
        const remainingHours = Math.round(hours % 24);
        return `${days}d ${remainingHours}h`;
    }

    function getInitials(name: string): string {
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    }

    function getStatusColor(status: string): string {
        const colors: Record<string, string> = {
            backlog: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
            todo: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            today: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            done: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    }
</script>

<AppLayout breadcrumbs={[{ title: 'Reports', href: '/reports' }]}>
    <AppHead title="User Performance Reports" />

    <div class="container mx-auto py-8 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">User Performance Reports</h1>
                <p class="text-muted-foreground">
                    Track task completion and performance metrics for each user
                </p>
            </div>
        </div>

        <!-- Date Range Filter -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <BarChart3 class="h-5 w-5" />
                    Date Range
                </CardTitle>
                <CardDescription>
                    Select the time period for the report
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex gap-2">
                    {#each dateRanges as days (days)}
                        <button
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors {dateRange === days
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-secondary text-secondary-foreground hover:bg-secondary/80'}"
                            onclick={() => changeDateRange(days)}
                        >
                            {days} days
                        </button>
                    {/each}
                </div>
            </CardContent>
        </Card>

        <!-- Summary Cards -->
        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                    <BarChart3 class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{userReports.length}</div>
                    <p class="text-xs text-muted-foreground">
                        Active users in the last {dateRange} days
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Tasks Completed</CardTitle>
                    <CheckCircle class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {userReports.reduce((sum, r) => sum + r.completed_tasks, 0)}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Tasks moved to done in {dateRange} days
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Average Completion Time</CardTitle>
                    <Timer class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {formatHours(
                            userReports
                                .filter((r) => r.avg_completion_time_hours !== null)
                                .reduce((sum, r, _, arr) => sum + (r.avg_completion_time_hours ?? 0) / arr.length, 0)
                        )}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Per task (from today → done)
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- User Performance Table -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Clock class="h-5 w-5" />
                    User Performance Details
                </CardTitle>
                <CardDescription>
                    Sorted by number of completed tasks (highest first)
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="w-full overflow-x-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="[&_tr]:border-b">
                            <tr class="border-b transition-colors hover:bg-muted/50">
                                <th class="h-12 px-4 text-left align-middle font-medium">User</th>
                                <th class="h-12 px-4 text-center align-middle font-medium">Total Tasks</th>
                                <th class="h-12 px-4 text-center align-middle font-medium">Completed</th>
                                <th class="h-12 px-4 text-center align-middle font-medium">Avg. Completion Time</th>
                                <th class="h-12 px-4 text-center align-middle font-medium">Status Breakdown</th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            {#each userReports as report (report.user.id)}
                                <tr class="border-b transition-colors hover:bg-muted/50">
                                    <td class="p-4 font-medium">
                                        <div class="flex items-center gap-3">
                                            <Avatar>
                                                <AvatarFallback>
                                                    {getInitials(report.user.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-semibold">{report.user.name}</div>
                                                <div class="text-sm text-muted-foreground">
                                                    {report.user.email}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="text-lg font-semibold">
                                            {report.total_tasks}
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="text-lg font-semibold text-green-600 dark:text-green-400">
                                            {report.completed_tasks}
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="inline-flex items-center gap-1">
                                            <Timer class="h-4 w-4" />
                                            {formatHours(report.avg_completion_time_hours)}
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-center gap-1 flex-wrap">
                                            <Badge variant="secondary" class={getStatusColor('backlog')}>
                                                Backlog: {report.status_breakdown.backlog}
                                            </Badge>
                                            <Badge variant="secondary" class={getStatusColor('todo')}>
                                                Todo: {report.status_breakdown.todo}
                                            </Badge>
                                            <Badge variant="secondary" class={getStatusColor('today')}>
                                                Today: {report.status_breakdown.today}
                                            </Badge>
                                            <Badge variant="secondary" class={getStatusColor('done')}>
                                                Done: {report.status_breakdown.done}
                                            </Badge>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>

                {#if userReports.length === 0}
                    <div class="py-12 text-center text-muted-foreground">
                        No user data available for the selected date range
                    </div>
                {/if}
            </CardContent>
        </Card>
    </div>
</AppLayout>
