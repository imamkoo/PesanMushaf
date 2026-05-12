export type InteractionRecoveryOptions = {
    removeClosedArtifacts?: boolean;
};

const BODY_STYLE_PROPERTIES = [
    'pointer-events',
    'overflow',
    'overflow-x',
    'overflow-y',
] as const;

const DOCUMENT_STYLE_PROPERTIES = ['overflow', 'overflow-x', 'overflow-y'] as const;

const SCROLL_LOCK_ATTRIBUTES = ['data-scroll-locked'] as const;

const APP_ROOT_SELECTOR = ['#app', '[data-page]'].join(', ');

const CLOSED_RADIX_ARTIFACTS_SELECTOR = [
    '[data-slot="sheet-overlay"][data-state="closed"]',
    '[data-slot="sheet-content"][data-state="closed"]',
    '[data-slot="dialog-overlay"][data-state="closed"]',
    '[data-slot="dialog-content"][data-state="closed"]',
    '[data-slot="dropdown-menu-content"][data-state="closed"]',
].join(', ');

function clearStyleProperties(
    element: HTMLElement,
    properties: readonly string[],
): void {
    properties.forEach((property) => element.style.removeProperty(property));
}

function clearAttributes(
    element: Element,
    attributes: readonly string[],
): void {
    attributes.forEach((attribute) => element.removeAttribute(attribute));
}

function clearAppRootInteractionState(): void {
    document.querySelectorAll<HTMLElement>(APP_ROOT_SELECTOR).forEach((element) => {
        element.removeAttribute('inert');
        element.removeAttribute('aria-hidden');
    });
}

function removeClosedRadixArtifacts(): void {
    document
        .querySelectorAll<HTMLElement>(CLOSED_RADIX_ARTIFACTS_SELECTOR)
        .forEach((element) => element.remove());
}

export function recoverInteractionState(
    options: InteractionRecoveryOptions = {},
): void {
    if (typeof document === 'undefined') {
        return;
    }

    const { removeClosedArtifacts = true } = options;

    clearStyleProperties(document.body, BODY_STYLE_PROPERTIES);
    clearStyleProperties(document.documentElement, DOCUMENT_STYLE_PROPERTIES);

    clearAttributes(document.body, SCROLL_LOCK_ATTRIBUTES);
    clearAttributes(document.documentElement, SCROLL_LOCK_ATTRIBUTES);

    clearAppRootInteractionState();

    if (removeClosedArtifacts) {
        removeClosedRadixArtifacts();
    }
}

export function scheduleInteractionRecovery(
    options: InteractionRecoveryOptions = {},
): () => void {
    if (typeof window === 'undefined') {
        return () => undefined;
    }

    let animationFrame = 0;
    const timeout = window.setTimeout(() => {
        recoverInteractionState(options);
    }, 150);

    animationFrame = window.requestAnimationFrame(() => {
        recoverInteractionState(options);
    });

    return () => {
        window.cancelAnimationFrame(animationFrame);
        window.clearTimeout(timeout);
    };
}
