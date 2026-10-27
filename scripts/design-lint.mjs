#!/usr/bin/env node
/**
 * Design consistency lint.
 *
 * Guards the scales documented in docs/reviews/2026-10-27-design-consistency.md:
 * - type sizes come from the token scale, never `text-[13px]`
 * - spacing uses the allowed steps (no 2.5 / 3.5 / 5)
 * - radius uses the three levels (no `rounded-xl`, prefer a token over bare `rounded`)
 * - colours use semantic tokens (palette hits are warnings; the board header is
 *   an intentional exception until it moves to status tokens)
 *
 * Usage:
 *   node scripts/design-lint.mjs           # errors fail, warnings report
 *   node scripts/design-lint.mjs --strict  # warnings fail too
 */

import fs from 'node:fs';
import path from 'node:path';

const ROOT = 'resources/js';
const IGNORED_DIRS = ['components/ui'];
const STRICT = process.argv.includes('--strict');

const RULES = [
    {
        id: 'type-size',
        level: 'error',
        message: 'arbitrary font size — use text-3xs / text-2xs / text-xs / text-dense / text-sm / text-base / text-xl / text-2xl / text-3xl',
        pattern: /text-\[[\d.]+px\]/g,
    },
    {
        id: 'spacing-step',
        level: 'error',
        message: 'off-scale spacing — allowed steps are 0.5, 1, 1.5, 2, 3, 4, 6, 8, 10',
        pattern: /(?<![\w-])(?:p|px|py|pt|pb|pl|pr|m|mx|my|mt|mb|ml|mr|gap|gap-x|gap-y|space-x|space-y)-(?:2\.5|3\.5|5)(?![\d.])/g,
    },
    {
        id: 'radius-xl',
        level: 'error',
        message: 'rounded-xl is outside the radius scale — use rounded-lg (8px) for dialogs/sheets',
        pattern: /rounded-xl/g,
    },
    {
        id: 'radius-bare',
        level: 'warn',
        message: 'bare `rounded` — prefer rounded-sm (chip), rounded-md (panel) or rounded-lg (dialog)',
        pattern: /(?<![\w-])rounded(?![\w-])/g,
    },
    {
        id: 'palette-colour',
        level: 'warn',
        message: 'raw palette colour — prefer the semantic token (success/info/routine/warning/destructive/muted)',
        pattern: /(?<![\w-])(?:bg|text|border|ring|fill|stroke)-(?:red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|slate|gray|zinc|neutral|stone)-\d{2,3}\b/g,
    },
];

function walk(dir) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    const files = [];

    for (const entry of entries) {
        const full = path.join(dir, entry.name);

        if (entry.isDirectory()) {
            if (IGNORED_DIRS.some((ignored) => full.replaceAll('\\', '/').includes(ignored))) {
                continue;
            }

            files.push(...walk(full));
        } else if (entry.name.endsWith('.svelte') || entry.name.endsWith('.ts')) {
            files.push(full);
        }
    }

    return files;
}

const findings = [];

for (const file of walk(ROOT)) {
    const lines = fs.readFileSync(file, 'utf8').split('\n');

    lines.forEach((line, index) => {
        for (const rule of RULES) {
            rule.pattern.lastIndex = 0;

            const matches = [...line.matchAll(rule.pattern)];

            for (const match of matches) {
                findings.push({
                    file,
                    line: index + 1,
                    rule,
                    match: match[0],
                });
            }
        }
    });
}

const errors = findings.filter((finding) => finding.rule.level === 'error');
const warnings = findings.filter((finding) => finding.rule.level === 'warn');

const byFile = new Map();

for (const finding of findings) {
    if (!byFile.has(finding.file)) {
        byFile.set(finding.file, []);
    }

    byFile.get(finding.file).push(finding);
}

for (const [file, items] of [...byFile.entries()].sort()) {
    console.log(`\n${file}`);

    for (const item of items.sort((a, b) => a.line - b.line)) {
        const tag = item.rule.level === 'error' ? 'error' : 'warn ';
        console.log(`  ${item.line}:${' '.repeat(0)} ${tag} ${item.match} — ${item.rule.message}`);
    }
}

const summary = new Map();

for (const finding of findings) {
    summary.set(finding.rule.id, (summary.get(finding.rule.id) ?? 0) + 1);
}

console.log(`\n${errors.length} error(s), ${warnings.length} warning(s) in ${byFile.size} file(s)`);

for (const [id, count] of summary) {
    console.log(`  ${id}: ${count}`);
}

process.exit(errors.length > 0 || (STRICT && warnings.length > 0) ? 1 : 0);
