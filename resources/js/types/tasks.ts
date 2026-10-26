export type CalendarUser = {
    id: number;
    name: string;
};

export type CalendarEvent = {
    id: number;
    title: string;
    reference: string | null;
    user: CalendarUser;
    at: string;
    deleted: boolean;
};

export type CalendarDay = {
    today: CalendarEvent[];
    done: CalendarEvent[];
    rollback: CalendarEvent[];
    planned: CalendarEvent[];
};

export type MolorTask = {
    id: number;
    title: string;
    status: string;
    user: CalendarUser;
    today_count: number;
    rollback_count: number;
};

export type CalendarSummary = {
    completed: number;
    today_entries: number;
    rollbacks: number;
    planned: number;
    top_molor: MolorTask | null;
};

export type CalendarThresholds = {
    today: number;
    rollback: number;
    stale_days: number;
};

export type TaskStatus = 'backlog' | 'todo' | 'today' | 'done';

export type BoardUser = {
    id: number;
    name: string;
    email: string;
};

export type BoardTask = {
    id: number;
    title: string;
    description: string | null;
    reference: string | null;
    status: TaskStatus;
    position: number | null;
    planned_on: string | null;
    created_at: string;
    completed_at: string | null;
    user: BoardUser;
    history: {
        id: number;
        from_status: string | null;
        to_status: string;
        created_at: string;
    }[];
};

export type TaskMetric = {
    today_count: number;
    rollback_count: number;
    stuck_days: number | null;
    is_molor: boolean;
    is_stale: boolean;
};

export type TaskMetrics = Record<number, TaskMetric>;

export type ChecklistStatus = 'done' | 'skipped' | 'pending';

export type ChecklistItem = {
    id: number;
    title: string;
    position: number;
    status: ChecklistStatus;
    checked_at: string | null;
};

export type ChecklistStats = {
    total: number;
    done: number;
    skipped: number;
    pending: number;
    percent: number;
    streak: number;
};

export type ChecklistTeamMember = {
    id: number;
    name: string;
    done: number;
    skipped: number;
    total: number;
};

export type ArchivedRoutine = {
    id: number;
    title: string;
    archived_on: string;
};

export type ChecklistDayItem = {
    id: number;
    title: string;
    status: ChecklistStatus;
};

export type ChecklistDay = {
    total: number;
    done: number;
    skipped: number;
    pending: number;
    note: string | null;
    items: ChecklistDayItem[];
};

export type ChecklistProps = {
    owner: CalendarUser;
    read_only: boolean;
    date: string;
    items: ChecklistItem[];
    note: { body: string; updated_at: string | null } | null;
    stats: ChecklistStats;
    archived: ArchivedRoutine[];
};

export type CalendarProps = {
    month: string;
    grid: {
        start: string;
        end: string;
    };
    days: Record<string, CalendarDay>;
    summary: CalendarSummary;
    checklist: ChecklistProps;
    checklist_by_date: Record<string, ChecklistDay>;
    checklist_team: ChecklistTeamMember[];
    molorTasks: MolorTask[];
    users: CalendarUser[];
    filters: {
        user_id: number | null;
    };
    thresholds: CalendarThresholds;
};

export type UserFilterOption = {
    id: number;
    name: string;
};

export type UserFilterProgress = {
    done: number;
    skipped: number;
    total: number;
};
