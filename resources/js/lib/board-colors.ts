/**
 * Board column accents.
 *
 * These are *identity* colours — every stage keeps its own hue so people can
 * tell columns apart at a glance — not semantic status. They therefore use the
 * raw Tailwind palette on purpose and are exempt from `npm run lint:design`
 * (see ALLOWED_FILES in scripts/design-lint.mjs).
 *
 * Status semantics (done / delayed / routine / overdue) live in the theme as
 * --success / --info / --routine / --warning, exposed as text-success etc.
 */

export const taskBoardHeaderStyles: Record<string, string> = {
    backlog: 'bg-muted/50',
    todo: 'bg-orange-100/50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-800 text-orange-800 dark:text-orange-200',
    today: 'bg-blue-100/50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800 text-blue-800 dark:text-blue-200',
    done: 'bg-neon-green/5 border-neon-green/20 text-neon-green',
};

export const featureBoardHeaderStyles: Record<string, string> = {
    new: 'bg-blue-100/50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800 text-blue-800 dark:text-blue-200',
    planning:
        'bg-yellow-100/50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200',
    development:
        'bg-purple-100/50 border-purple-200 dark:bg-purple-900/20 dark:border-purple-800 text-purple-800 dark:text-purple-200',
    done: 'bg-orange-100/50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-800 text-orange-800 dark:text-orange-200',
    released:
        'bg-green-100/50 border-green-200 dark:bg-green-900/20 dark:border-green-800 text-green-800 dark:text-green-200',
};

export const featureStatusBadgeStyles: Record<string, string> = {
    new: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    planning:
        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    development:
        'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    done: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    released:
        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
};
