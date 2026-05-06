# Task Priority Reordering — Design Spec

**Date:** 2026-05-06
**Scope:** Backlog and Todo columns only (Today and Done are not reorderable)
**Ordering:** Per-user, per-column, persisted to database

---

## 1. Database

### Migration
Add a nullable `position` column (double precision) to the `tasks` table.

```sql
ALTER TABLE tasks ADD COLUMN position DOUBLE PRECISION NULL;
```

### Rules
- Only tasks with `status = 'backlog'` or `status = 'todo'` have a non-null `position`.
- When a task is created in backlog/todo: assign `position = max(position in same status+user) + 1.0`.
- When a task is moved to today/done: set `position = null`.
- When a task is moved back to backlog/todo: assign `position = max(position in same status+user) + 1.0` (append at bottom).

### Rebalance
After any position update, if the gap between any two adjacent positions drops below `0.001`, reassign all positions in that column (same `status` + `user_id`) as sequential integers: `1.0, 2.0, 3.0, ...`

---

## 2. Backend

### New Endpoint
`PATCH /tasks/{task}/reorder`

**Authorization:** Task must belong to the authenticated user.

**Validation:**
- `position` (required, numeric)
- Task `status` must be `backlog` or `todo` — return 422 otherwise.

**Logic:**
1. Update `task.position` to the provided value.
2. Query all tasks in the same `status` + `user_id`, ordered by `position`.
3. Check if any adjacent gap < `0.001`. If so, rebalance (reassign `1.0, 2.0, 3.0, ...`).
4. Return the updated task (or 204 if no rebalance needed).

### Position Calculation (done on frontend)
To insert between task A (position `p1`) and task B (position `p2`):
```
new_position = (p1 + p2) / 2
```
To move to top: `position = first_task.position / 2`
To move to bottom: `position = last_task.position + 1.0`

---

## 3. Frontend

### UI
- Backlog and Todo columns: show `↑` `↓` buttons on each task card (visible on hover).
- `↑` is disabled when the task is first in the list.
- `↓` is disabled when the task is last in the list.
- Today and Done columns: no reorder buttons, no position-based sorting.

### Behavior
1. User clicks `↑` or `↓`.
2. Frontend computes new `position` value based on neighbor positions.
3. Optimistically reorder the local list immediately.
4. Send `PATCH /tasks/{task}/reorder` with the new `position`.
5. On error: revert local list to previous order.

### Sorting
- Backlog and Todo columns: sort ascending by `position`.
- Today column: unchanged (existing behavior).
- Done column: unchanged (grouped by completion date, newest first).

---

## Out of Scope
- Drag-and-drop reordering within a column (arrow buttons only).
- Reordering across columns (position only applies within same status).
- Priority ordering for Today and Done columns.
