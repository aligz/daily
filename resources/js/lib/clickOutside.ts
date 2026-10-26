type ClickOutsideOptions = {
    handler: () => void;
    /**
     * Elements matching this selector are ignored (for example the button
     * that toggles the popover), so the popover can close itself normally.
     */
    ignore?: string;
};

export function clickOutside(node: HTMLElement, options: ClickOutsideOptions) {
    let current = options;

    const handlePointerDown = (event: PointerEvent) => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        if (node.contains(target)) {
            return;
        }

        if (current.ignore && target.closest(current.ignore)) {
            return;
        }

        current.handler();
    };

    document.addEventListener('pointerdown', handlePointerDown, true);

    return {
        update(next: ClickOutsideOptions) {
            current = next;
        },
        destroy() {
            document.removeEventListener('pointerdown', handlePointerDown, true);
        },
    };
}
