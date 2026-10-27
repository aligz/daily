export type DialogContext = {
    open: () => boolean;
    setOpen: (value: boolean) => void;
    titleId: string;
    descriptionId: string;
};

export const DIALOG_CONTEXT = Symbol('dialog');

let dialogCounter = 0;

export function nextDialogId(): number {
    dialogCounter += 1;

    return dialogCounter;
}
