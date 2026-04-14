---

## Project Overview

This is a **Laravel + Svelte + Inertia** single-page application built as a Kanban-style task board. It uses the Laravel Svelte Starter Kit with Fortify for authentication, Inertia.js for SPA routing, Svelte 5 for the frontend, and Tailwind CSS v4 for styling.

---

## 1. Project Structure

### Top-level Directories
```
/Users/restock/Documents/Sites/daily/
  app/              - PHP application code (Models, Controllers, Actions, Middleware)
  bootstrap/        - Laravel bootstrap files
  config/           - Laravel configuration
  database/         - Migrations, factories, seeders
  public/           - Public web root
  resources/        - Frontend assets (JS/Svelte, CSS, Blade views)
  routes/           - Route definitions
  storage/          - Storage for logs, cache, views
  tests/            - Pest/PHPUnit tests
```

---

## 2. Task Model

**File:** `/Users/restock/Documents/Sites/daily/app/Models/Task.php`

### Database Schema (from migrations)

**Migration:** `/Users/restock/Documents/Sites/daily/database/migrations/2026_02_18_045621_create_tasks_table.php`
**Migration:** `/Users/restock/Documents/Sites/daily/database/migrations/2026_02_18_065640_add_completed_at_to_tasks_table.php`

The `tasks` table has these columns:

| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | Auto-increment |
| `user_id` | bigint (FK) | References `users.id`, cascade on delete |
| `title` | string | Required |
| `description` | text | Nullable |
| `reference` | string(50) | Nullable (e.g., "JIRA-123") |
| `status` | string | Default `'backlog'`, values: `backlog`, `todo`, `today`, `done` |
| `completed_at` | timestamp | Nullable, set when status becomes `done` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft deletes enabled |

### Fillable Fields
- `user_id`, `title`, `description`, `reference`, `status`, `completed_at`

### Casts
- `created_at`, `updated_at`, `completed_at` cast to `datetime`

### Relationships
- `belongsTo(User)` - each task belongs to a user
- `hasMany(TaskHistory)` - each task has many history entries

### Status Board Transitions (Key Business Logic in TaskController)

From `/Users/restock/Documents/Sites/daily/app/Http/Controllers/TaskController.php`:

- There is **only ONE task allowed in the "today" board at a time**. When a task moves to `today`, any existing `today` task is automatically moved back to `todo`.
- When a task moves to `done`, `completed_at` is set to `now()`.
- When a task moves away from `done`, `completed_at` is cleared to `null`.
- A daily scheduled job (in `routes/console.php`) resets ALL `today` tasks back to `todo` at midnight, recording history for each.

---

## 3. TaskHistory Model

**File:** `/Users/restock/Documents/Sites/daily/app/Models/TaskHistory.php`

### Database Schema (from migration)

**Migration:** `/Users/restock/Documents/Sites/daily/database/migrations/2026_02_18_045624_create_task_histories_table.php`

The `task_histories` table has these columns:

| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | Auto-increment |
| `task_id` | bigint (FK) | References `tasks.id`, cascade on delete |
| `from_status` | string | Nullable (null on creation) |
| `to_status` | string | Required |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Fillable Fields
- `task_id`, `from_status`, `to_status`

### Relationship
- `belongsTo(Task)` - each history entry belongs to a task

### History Tracking

History is recorded in two scenarios:
1. **Task creation** (`store` method): Creates a history entry with `from_status = null` and `to_status = initial status`.
2. **Task status change** (`update` method): When `from_status !== to_status`, creates a history entry tracking the transition.

---

## 4. User Model

**File:** `/Users/restock/Documents/Sites/daily/app/Models/User.php`

### Database Schema (from migration)

**Migration:** `/Users/restock/Documents/Sites/daily/database/migrations/0001_01_01_000000_create_users_table.php`
**Migration:** `/Users/restock/Documents/Sites/daily/database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php`

Standard Laravel auth user model with Fortify two-factor support.

### Fillable Fields
- `name`, `email`, `password`

### Hidden Fields
- `password`, `two_factor_secret`, `two_factor_recovery_codes`, `remember_token`

### Casts
- `email_verified_at` -> `datetime`
- `password` -> `hashed`
- `two_factor_confirmed_at` -> `datetime`

### Traits
- `HasFactory`, `Notifiable`, `TwoFactorAuthenticatable`

### Relationship
- `hasMany(Task)` - a user has many tasks

### TypeScript Type (from `/Users/restock/Documents/Sites/daily/resources/js/types/auth.ts`)

```typescript
export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};
```

---

## 5. Controllers

### TaskController

**File:** `/Users/restock/Documents/Sites/daily/app/Http/Controllers/TaskController.php`

This is the **only domain controller** (aside from Settings controllers). It handles full CRUD for tasks:

| Method | Route | Description |
|---|---|---|
| `index()` | GET `/tasks` | Lists all tasks grouped by status, with users. Renders `Tasks/Index` Svelte page. |
| `store()` | POST `/tasks` | Creates a task + initial history entry. |
| `update()` | PUT `/tasks/{task}` | Updates task fields, handles status transitions, enforces single-"today" rule, manages `completed_at`. |
| `destroy()` | DELETE `/tasks/{task}` | Deletes a task (owner only). |

### Settings Controllers

- `/Users/restock/Documents/Sites/daily/app/Http/Controllers/Settings/ProfileController.php`
- `/Users/restock/Documents/Sites/daily/app/Http/Controllers/Settings/PasswordController.php`
- `/Users/restock/Documents/Sites/daily/app/Http/Controllers/Settings/TwoFactorAuthenticationController.php`

### Fortify Actions

- `/Users/restock/Documents/Sites/daily/app/Actions/Fortify/CreateNewUser.php`
- `/Users/restock/Documents/Sites/daily/app/Actions/Fortify/ResetUserPassword.php`

---

## 6. Routing Structure

### Web Routes

**File:** `/Users/restock/Documents/Sites/daily/routes/web.php`

```php
// Public
GET  '/'                        -> Welcome page (or redirect to tasks if authenticated)

// Auth redirect
GET  '/dashboard'               -> Redirects to tasks.index (middleware: auth, verified)

// Authenticated task routes (resource controller)
GET    /tasks                   -> tasks.index   (TaskController@index)
POST   /tasks                   -> tasks.store   (TaskController@store)
GET    /tasks/{task}            -> tasks.show    (not implemented)
PUT    /tasks/{task}            -> tasks.update  (TaskController@update)
DELETE /tasks/{task}            -> tasks.destroy (TaskController@destroy)
```

**File:** `/Users/restock/Documents/Sites/daily/routes/settings.php`

```php
GET    /settings/profile        -> profile.edit
PATCH  /settings/profile        -> profile.update
DELETE /settings/profile        -> profile.destroy
GET    /settings/password       -> user-password.edit
PUT    /settings/password       -> user-password.update
GET    /settings/appearance     -> appearance.edit (Inertia render)
GET    /settings/two-factor     -> two-factor.show
```

**File:** `/Users/restock/Documents/Sites/daily/routes/console.php`

Defines a **daily scheduled job** that resets all `today` status tasks back to `todo` and records the history.

---

## 7. Frontend Pages and UI Components

### Pages

| Page | File |
|---|---|
| Tasks Index (Kanban Board) | `/Users/restock/Documents/Sites/daily/resources/js/pages/Tasks/Index.svelte` |
| Welcome | `/Users/restock/Documents/Sites/daily/resources/js/pages/Welcome.svelte` |
| Profile Settings | `/Users/restock/Documents/Sites/daily/resources/js/pages/settings/Profile.svelte` |
| Password Settings | `/Users/restock/Documents/Sites/daily/resources/js/pages/settings/Password.svelte` |
| Appearance Settings | `/Users/restock/Documents/Sites/daily/resources/js/pages/settings/Appearance.svelte` |
| Two-Factor Settings | `/Users/restock/Documents/Sites/daily/resources/js/pages/settings/TwoFactor.svelte` |
| Auth Pages | `/Users/restock/Documents/Sites/daily/resources/js/pages/auth/` (Login, Register, ForgotPassword, ResetPassword, VerifyEmail, ConfirmPassword, TwoFactorChallenge) |

### Layouts

| Layout | File |
|---|---|
| Main App Layout | `/Users/restock/Documents/Sites/daily/resources/js/layouts/AppLayout.svelte` |
| Header Kanban Layout | `/Users/restock/Documents/Sites/daily/resources/js/layouts/app/AppHeaderKanbanLayout.svelte` |
| Auth Layout | `/Users/restock/Documents/Sites/daily/resources/js/layouts/AuthLayout.svelte` |

### Key UI Components

- `/Users/restock/Documents/Sites/daily/resources/js/components/AppSidebar.svelte` - Sidebar with "Tasks" nav item (icon: LayoutGrid)
- `/Users/restock/Documents/Sites/daily/resources/js/components/AppHeader.svelte`
- `/Users/restock/Documents/Sites/daily/resources/js/components/NavMain.svelte`
- `/Users/restock/Documents/Sites/daily/resources/js/components/ui/` - shadcn-svelte UI component library (button, card, dialog, input, badge, avatar, etc.)

### Task Board UI (Index.svelte)

The Kanban board page (`/Users/restock/Documents/Sites/daily/resources/js/pages/Tasks/Index.svelte`) features:
- Four columns: `backlog`, `todo`, `today`, `done`
- Drag-and-drop task cards between columns
- User filter dropdown to filter tasks by user
- Create/Edit task dialog with history display
- Delete task capability (owner only)
- "Done" column groups tasks by `completed_at` date (localized to Indonesian)
- Board headers are color-coded (done=green, today=blue, todo=orange)

---

## 8. Existing Report/Analytics Pages

**There are NO existing report or analytics pages.** The project currently has:
- A task Kanban board (the main page)
- Settings pages (profile, password, appearance, two-factor)
- Auth pages

The dashboard route (`/dashboard`) simply redirects to `/tasks`. There is no separate analytics, reporting, or statistics functionality.

---

## 9. Database Factories and Seeders

- `/Users/restock/Documents/Sites/daily/database/factories/UserFactory.php` - Creates users with name, email, password, 2FA state
- `/Users/restock/Documents/Sites/daily/database/factories/TaskFactory.php` - Creates tasks with faker data, random status from `backlog/todo/today/done`
- `/Users/restock/Documents/Sites/daily/database/seeders/DatabaseSeeder.php` - Seeds a single "Test User"

---

## 10. Tests

**File:** `/Users/restock/Documents/Sites/daily/tests/Feature/TaskTest.php`

Comprehensive Pest tests covering:
- Dashboard redirects to tasks
- Task page loads with tasks and users
- Task creation (including history recording)
- Task update (title, description, reference)
- Status transitions and history tracking
- Single-"today" rule enforcement
- `completed_at` setting/clearing on done transition
- Daily schedule reset logic

---

## Summary of Key Findings for Reports/Analytics

**What is tracked:**
- Every status transition is recorded in `task_histories` with `from_status`, `to_status`, and `created_at` timestamp
- Tasks have `created_at`, `updated_at`, and `completed_at` timestamps
- Tasks have soft deletes (`deleted_at`)

**What is NOT tracked:**
- No dedicated "moved to today" timestamp on the Task model itself (only inferable from `task_histories` where `to_status = 'today'`)
- No "time in status" metrics
- No analytics or reporting pages exist

**How to infer "today board" time:**
- Query `task_histories` for entries where `to_status = 'today'` to find when a task entered the Today board
- The daily schedule resets today tasks, so a task typically stays in "today" for less than 24 hours

Now let me look at the specific files to understand the data structure better:



Perfect! Now I understand the structure. Let me create a plan for the report page:
