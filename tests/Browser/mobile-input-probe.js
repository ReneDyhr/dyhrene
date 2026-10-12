(() => {
    'use strict';

    const editableSelector = [
        'input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="color"]):not([type="button"]):not([type="submit"]):not([type="reset"]):not(:disabled):not([readonly])',
        'textarea:not(:disabled):not([readonly])',
        'select:not(:disabled):not([readonly])',
        '[contenteditable="true"]',
    ].join(',');

    const isVisible = (element) => {
        const style = window.getComputedStyle(element);
        const rect = element.getBoundingClientRect();

        return style.display !== 'none'
            && style.visibility !== 'hidden'
            && Number(style.opacity) !== 0
            && rect.width > 0
            && rect.height > 0;
    };

    const describe = (element) => {
        const style = window.getComputedStyle(element);
        const rect = element.getBoundingClientRect();

        return {
            selector: element.id ? `#${CSS.escape(element.id)}` : null,
            tag: element.tagName.toLowerCase(),
            type: element instanceof HTMLInputElement ? element.type : null,
            className: typeof element.className === 'string' ? element.className : null,
            fontSize: style.fontSize,
            visible: isVisible(element),
            rect: { x: rect.x, y: rect.y, width: rect.width, height: rect.height },
            focused: document.activeElement === element,
        };
    };

    const snapshot = (label) => {
        const visualViewport = window.visualViewport;
        const result = {
            label,
            timestamp: new Date().toISOString(),
            viewport: { width: window.innerWidth, height: window.innerHeight },
            screen: { width: window.screen.width, height: window.screen.height },
            anyPointerCoarse: window.matchMedia('(any-pointer: coarse)').matches,
            anyPointerFine: window.matchMedia('(any-pointer: fine)').matches,
            visualViewportScale: visualViewport ? visualViewport.scale : null,
            document: {
                clientWidth: document.documentElement.clientWidth,
                scrollWidth: document.documentElement.scrollWidth,
                horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
            },
            focused: document.activeElement.matches?.(editableSelector)
                ? describe(document.activeElement)
                : null,
            visibleEditableElements: [...document.querySelectorAll(editableSelector)]
                .filter(isVisible)
                .map(describe),
        };

        console.log('[mobile-input-probe]', result);
        return result;
    };

    const visibleEditableElements = () => [...document.querySelectorAll(editableSelector)].filter(isVisible);

    const api = {
        snapshot,
        blur() {
            if (document.activeElement instanceof HTMLElement) {
                document.activeElement.blur();
            }
            return snapshot('blurred');
        },
        reset() {
            if (document.activeElement instanceof HTMLElement) {
                document.activeElement.blur();
            }
            window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
            return snapshot('reset');
        },
        async focusEach() {
            const elements = visibleEditableElements();
            const results = [];

            for (const [index, element] of elements.entries()) {
                if (!element.isConnected || !isVisible(element)) continue;
                element.focus({ preventScroll: true });
                results.push(snapshot(`focused ${index + 1}/${elements.length}`));
                element.blur();
            }

            snapshot('after focus sweep');
            return results;
        },
    };

    window.mobileInputProbe = api;
    snapshot('start');
    console.info('Run mobileInputProbe.focusEach(), .snapshot(label), .blur(), or .reset() in the console.');
})();
