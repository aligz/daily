import type { GuideCopy } from './types';

export const en: GuideCopy = {
    languageLabel: 'Guide language',
    tocLabel: 'On this page',
    eyebrow: 'Guide',
    title: 'How to use Daily',
    intro: 'Three places, one habit: plan the day, do the work, look back. This page explains every screen in plain language. Read it once, then come back whenever something looks unfamiliar.',
    openBoard: 'Open the board',
    openCalendar: 'Open the calendar',
    summary: [
        {
            title: 'Board',
            body: 'Where work waits and moves. You drag cards here.',
        },
        {
            title: 'Calendar',
            body: 'What actually happened on each day. It fills itself in.',
        },
        {
            title: 'Routine',
            body: 'The few things you do every day. One click to tick them.',
        },
    ],
    toc: [
        { id: 'board', title: 'The board' },
        { id: 'cards', title: 'Reading a card' },
        { id: 'calendar', title: 'The calendar' },
        { id: 'routine', title: 'Your routine' },
        { id: 'team', title: 'Working as a team' },
        { id: 'habits', title: 'Habits that help' },
        { id: 'questions', title: 'Quick answers' },
    ],
    board: {
        title: 'The board',
        intro: 'The board is where work moves. Each column is a stage; cards travel left to right as work gets closer to done. A card belongs to one person, and only that person moves it.',
        columns: [
            {
                name: 'Planning',
                what: 'Things worth doing, not yet promised.',
                how: 'Park ideas here without committing to them.',
            },
            {
                name: 'Todo',
                what: 'Agreed work, waiting for a slot.',
                how: 'This is your queue. Pick from here when Today is empty.',
            },
            {
                name: 'Today',
                what: 'What you are doing right now.',
                how: 'Keep it to one to three cards you can actually finish.',
            },
            {
                name: 'Done',
                what: 'Finished work, grouped by finish date.',
                how: 'Drag a card here once it is really finished.',
            },
        ],
        movingTitle: 'Moving work',
        moving: [
            {
                lead: 'Drag',
                body: 'a card to another column. The change saves as soon as you drop it.',
            },
            {
                lead: 'Order',
                body: 'Planning and Todo stay sorted newest first, so a card you just added is already at the top.',
            },
            {
                lead: 'Click',
                body: 'a card to open it, edit the details, or delete it. Other people’s cards open read-only.',
            },
        ],
        addingTitle: 'Adding a task',
        addingIntro:
            'Use the New Task button at the top right. Only the title is required.',
        fields: [
            {
                label: 'Title',
                text: 'the outcome, not the activity. “Ship the login fix”, not “Look at login”.',
            },
            {
                label: 'Reference',
                text: 'the ticket number if there is one, such as JIRA-123. It appears on the card and in the calendar.',
            },
            {
                label: 'Planned for',
                text: 'an optional future date, for when you intend to start. It does not push the card into Today, and past dates are disabled.',
            },
            {
                label: 'Description',
                text: 'any detail your future self will need.',
            },
        ],
        resetTitle: 'Every night at midnight (Jakarta time)',
        resetBody:
            'The app moves whatever is left in Today back to Todo. That keeps Today meaningful, and the calendar records the move so you can see what slipped.',
    },
    cards: {
        title: 'Reading a card',
        intro: 'Small chips tell you why a card is worth a look. A card shows at most two of them. Hover a chip for the exact numbers.',
        legend: [
            {
                chip: '▶ Focus today',
                meaning: 'The card sits in the Today column right now.',
            },
            {
                chip: '◇ Plan 12 Nov',
                meaning:
                    'The day you planned to pick it up. It also shows on the calendar.',
            },
            {
                chip: 'Stale 21d',
                meaning:
                    'Nobody touched it for a long time (14 days by default). Split it, schedule it, or drop it.',
            },
            {
                chip: '4× today · 3↩',
                meaning:
                    'It keeps coming back: three or more visits to Today, or two or more slip-backs. A hint that it is too big.',
            },
            {
                chip: '✓ Done 26 Oct',
                meaning: 'When the task was finished.',
            },
        ],
        footnote:
            '“Stale” counts days without a change. “Delayed” counts trips through Today and slip-backs — currently 3 visits or 2 slip-backs.',
    },
    calendar: {
        title: 'The calendar',
        intro: 'The calendar is written by the board, so you never maintain it twice. Move cards and tick routines; the days fill themselves in. Switch to it with the Board / Calendar toggle at the top right of the Tasks page.',
        monthTitle: 'Reading a month',
        month: [
            'Weeks start on Monday. Today is highlighted and weekends are tinted.',
            'A day shows up to three entries: a blue bar means the card entered Today, green means completed, a dashed ◇ means planned. The small number is how many entries that day has.',
            'A red edge on top plus “↩ n rolled back” means work slipped back to Todo that day.',
            'The violet line at the bottom is that day’s routine progress.',
        ],
        dayTitle: 'Opening one day',
        day: 'Click a day to open Day detail on the right. It groups the day into Today entries, Completed, Planned, and Rolled back, with names, initials, references, and times. Deleted tasks stay visible, struck through and marked “(deleted)”. If the day has routine activity, it appears at the top with each item and the day’s note.',
        boxes: [
            {
                title: 'The four numbers',
                body: 'Completed this month, Today entries, Rolled back, and Most delayed — a one-line health check of the month, not a scoreboard.',
            },
            {
                title: 'Moving around',
                body: 'Use the arrows for the previous or next month, and Today to jump back. With a day focused, the arrow keys walk the grid.',
            },
        ],
        footer: 'On a phone, the grid is replaced by an Agenda list of the same days, in order. Nothing on this page is editable — it is a record. If something looks wrong, fix the card on the board.',
    },
    routine: {
        title: 'Your routine',
        intro: 'Routine is the short list of things you do every day: standup, review a pull request, check alerts, write your update. The same list every day, one click to tick. It lives in the violet bar at the top of the calendar page and always shows today, whichever month you are browsing.',
        blocks: [
            {
                title: 'Tick and untick',
                body: 'One click marks it done. Click the same box again and it goes back to pending.',
            },
            {
                title: 'Skip a day it does not apply',
                body: 'Open ⋯ and choose Skip — for leave, a holiday, or a day you were blocked. It is counted as handled, tagged “skipped”, and it does not break your streak.',
            },
            {
                title: 'Leave one note for the day',
                body: 'Use Note for the thing that got in the way. One line per day, shown in the bar and on that day in the calendar — so “blocked on staging” is visible months later.',
            },
            {
                title: 'Add, rename, reorder, archive',
                body: 'Add routine sits at the bottom of the bar and starts counting today. ⋯ lets you edit the name, move it up or down, or archive it. Archiving hides it from today onward and keeps its history; there is no un-archive in this version.',
            },
        ],
        footnotes: [
            {
                lead: '🔥 streak',
                body: 'counts days in a row where everything active that day was done or skipped. Today does not break it while the day is still going.',
            },
            {
                lead: 'n/m',
                body: 'is handled out of total. Untouched routines count as pending, which is why a fresh day starts at 0.',
            },
            {
                body: 'The bar only checks today; past days are a record. And routines never appear on the board — habits are not tickets.',
            },
        ],
    },
    team: {
        title: 'Working as a team',
        intro: 'The filter at the top right switches between All users and one person. On the board it hides everyone else’s cards; on the calendar it narrows the days, the numbers, and the routine bar.',
        items: [
            {
                lead: 'Heads',
                body: 'also see each developer’s routine progress next to their name, and can open someone else’s routine to read it. That view is locked: heads cannot tick boxes for other people, so the record stays honest.',
            },
            {
                lead: 'Everyone else',
                body: 'can filter what they are looking at, but the routine bar stays their own.',
            },
            {
                lead: 'Reports',
                body: 'shows completed work per person and the average time from picking a task up to finishing it, for the last 7 to 90 days.',
            },
            {
                lead: 'Feature Requests',
                body: 'is a separate list for requests coming from other divisions, with its own priorities and deadlines. It is not part of your daily board.',
            },
        ],
    },
    habits: {
        title: 'Habits that help',
        intro: 'The app is simple on purpose. These six habits are what make it useful instead of just another tab.',
        items: [
            {
                lead: 'Keep Today small.',
                body: 'One to three cards you can genuinely finish. Today is a promise, not a wishlist.',
            },
            {
                lead: 'Tidy up before you leave.',
                body: 'Finished to Done, unfinished back to Todo. Tomorrow starts clean, and the calendar stays honest.',
            },
            {
                lead: 'Plan tomorrow before you log off.',
                body: 'Set “Planned for” on the card you will start with. Future you will not have to remember.',
            },
            {
                lead: 'Put the ticket number in Reference.',
                body: 'A short code such as JIRA-123 on the card makes it searchable later, in the calendar too.',
            },
            {
                lead: 'Tick your routine before you go.',
                body: 'Could not do it? Use Skip and leave the one-line note. Skipping is honest; silence is not.',
            },
            {
                lead: 'Trust the badges.',
                body: 'Stale or delayed is not a scolding. It is a signal to split the task, schedule it, or drop it.',
            },
        ],
    },
    questions: {
        title: 'Quick answers',
        items: [
            {
                q: 'A card I was working on vanished from Today.',
                a: 'At midnight (Jakarta time) the app moves whatever is left in Today back to Todo. Nothing is lost — the card is in Todo, and the calendar records the move as “rolled back”.',
            },
            {
                q: 'Why can’t I move or tick something?',
                a: 'You can only move your own cards. Routine boxes belong to their owner; heads can open other people’s routines, but only to look.',
            },
            {
                q: 'Why does the date picker refuse last week?',
                a: '“Planned for” is about the future, so past dates are disabled. If you need to keep older context, put it in the description.',
            },
            {
                q: 'I deleted a task. Why is it still on the calendar?',
                a: 'The calendar keeps history. A deleted task still appears, struck through and marked “(deleted)”, so past days stay accurate.',
            },
            {
                q: 'Where is my routine?',
                a: 'On the calendar page, in the violet bar above the monthly numbers. It is not on the board.',
            },
            {
                q: 'Can I bring back an archived routine?',
                a: 'Not in this version. Add it again as a new routine; the old entries stay visible on past days.',
            },
            {
                q: 'Does skipping a routine break my streak?',
                a: 'No. Done and skipped both count as handled. Only a day you never touched does.',
            },
            {
                q: 'Why do I see 0/3 before doing anything?',
                a: 'An untouched routine counts as pending, not completed. The number fills in as you tick boxes.',
            },
        ],
        outro: 'Still unsure about something? Ask in the team chat, or open the board and look at a real card — most answers are visible there.',
    },
};
