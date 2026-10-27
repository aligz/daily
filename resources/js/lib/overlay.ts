/**
 * Focus management for overlay surfaces (dialog, sheet).
 *
 * Keeps keyboard focus inside the surface, closes on Escape, locks background
 * scrolling while open and returns focus to the trigger on close.
 */

const FOCUSABLE_SELECTOR = [
    'a[href]',
    'area[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    'iframe',
    'audio[controls]',
    'video[controls]',
    '[contenteditable="true"]',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

export function focusableWithin(root: HTMLElement): HTMLElement[] {
    return Array.from(
        root.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR),
    ).filter((element) => {
        if (element.hasAttribute('disabled')) return false;
        if (element.getAttribute('aria-hidden') === 'true') return false;

        return element.getClientRects().length > 0;
    });
}

export type OverlayOptions = {
    onEscape?: () => void;
    label?: string;
};

export function overlay(node: HTMLElement, options: OverlayOptions = {}) {
    const previouslyFocused =
        document.activeElement instanceof HTMLElement
            ? document.activeElement
            : null;

    const body = document.body;
    const previousOverflow = body.style.overflow;
    const previousPaddingRight = body.style.paddingRight;
    const scrollbarWidth =
        window.innerWidth - document.documentElement.clientWidth;

    body.style.overflow = 'hidden';

    if (scrollbarWidth > 0) {
        body.style.paddingRight = `${scrollbarWidth}px`;
    }

    if (!node.hasAttribute('tabindex')) {
        node.setAttribute('tabindex', '-1');
    }

    const focusFirst = () => {
        const target = focusableWithin(node)[0] ?? node;
        target.focus({ preventScroll: true });
    };

    const frame = requestAnimationFrame(focusFirst);

    const handleDocumentKeydown = (event: KeyboardEvent) => {
        if (event.key !== 'Escape') return;

        event.stopPropagation();
        options.onEscape?.();
    };

    const handleKeydown = (event: KeyboardEvent) => {
        if (event.key !== 'Tab') return;

        const items = focusableWithin(node);

        if (items.length === 0) {
            event.preventDefault();
            node.focus({ preventScroll: true });

            return;
        }

        const first = items[0];
        const last = items[items.length - 1];
        const active = document.activeElement;

        if (event.shiftKey && (active === first || active === node)) {
            event.preventDefault();
            last.focus({ preventScroll: true });

            return;
        }

        if (!event.shiftKey && active === last) {
            event.preventDefault();
            first.focus({ preventScroll: true });
        }
    };

    document.addEventListener('keydown', handleDocumentKeydown, true);
    node.addEventListener('keydown', handleKeydown);

    return {
        destroy() {
            cancelAnimationFrame(frame);
            document.removeEventListener(
                'keydown',
                handleDocumentKeydown,
                true,
            );
            node.removeEventListener('keydown', handleKeydown);

            body.style.overflow = previousOverflow;
            body.style.paddingRight = previousPaddingRight;

            previouslyFocused?.focus({ preventScroll: true });
        },
    };
}
