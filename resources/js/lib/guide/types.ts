export type GuideLang = 'en' | 'id';

/**
 * A sentence with an optional bold lead-in. The lead is rendered in medium
 * weight, followed by a single space and the body.
 */
export type GuideLine = {
    lead?: string;
    body: string;
};

export type GuideSummary = {
    title: string;
    body: string;
};

export type GuideColumn = {
    name: string;
    what: string;
    how: string;
};

export type GuideField = {
    label: string;
    text: string;
};

export type GuideLegend = {
    chip: string;
    meaning: string;
};

export type GuideBlock = {
    title: string;
    body: string;
};

export type GuideQuestion = {
    q: string;
    a: string;
};

export type GuideTocItem = {
    id: string;
    title: string;
};

export type GuideCopy = {
    languageLabel: string;
    tocLabel: string;
    eyebrow: string;
    title: string;
    intro: string;
    openBoard: string;
    openCalendar: string;
    summary: GuideSummary[];
    toc: GuideTocItem[];
    board: {
        title: string;
        intro: string;
        columns: GuideColumn[];
        movingTitle: string;
        moving: GuideLine[];
        addingTitle: string;
        addingIntro: string;
        fields: GuideField[];
        resetTitle: string;
        resetBody: string;
    };
    cards: {
        title: string;
        intro: string;
        legend: GuideLegend[];
        footnote: string;
    };
    calendar: {
        title: string;
        intro: string;
        monthTitle: string;
        month: string[];
        dayTitle: string;
        day: string;
        boxes: GuideBlock[];
        footer: string;
    };
    routine: {
        title: string;
        intro: string;
        blocks: GuideBlock[];
        footnotes: GuideLine[];
    };
    team: {
        title: string;
        intro: string;
        items: GuideLine[];
    };
    habits: {
        title: string;
        intro: string;
        items: GuideLine[];
    };
    questions: {
        title: string;
        items: GuideQuestion[];
        outro: string;
    };
};
