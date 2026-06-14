const ensureJqueryShim = () => {
    if (globalThis.$ && globalThis.jQuery) {
        return;
    }

    const existing$ = globalThis.$ ?? globalThis.jQuery;
    if (existing$ && existing$.fn) {
        globalThis.$ = globalThis.$ ?? existing$;
        globalThis.jQuery = globalThis.jQuery ?? existing$;
    }
};

const parseJsonAttribute = (rawValue) => {
    if (!rawValue || typeof rawValue !== 'string') {
        return {};
    }

    try {
        return JSON.parse(rawValue);
    } catch (error) {
        const decoder = document.createElement('textarea');
        decoder.innerHTML = rawValue;
        const decoded = decoder.value;

        try {
            return JSON.parse(decoded);
        } catch (secondError) {
            return {};
        }
    }
};

const cleanupA11yesUi = () => {
    document.querySelectorAll('.a11yes-window').forEach((node) => node.remove());
    document.querySelectorAll('style').forEach((style) => {
        if (style.textContent && style.textContent.includes('a11yes-')) {
            style.remove();
        }
    });
};

const initA11yes = async () => {
    ensureJqueryShim();

    const { a11YesInit } = await import('../Contrib/a11yes.min.js');
    const a11yBtn = document.querySelector('.a11yes-open[data-params]');

    if (!a11yBtn) {
        return;
    }

    const stepKeys = {
        fontSize: 'a11yes-fontSizeStepsCount',
        letterSpacing: 'a11yes-letterSpacingStepsCount',
    };
    const resetKeys = [
        'a11yes-fontSizeStepsCount',
        'a11yes-letterSpacingStepsCount',
        'a11yes-fontType',
        'a11yes-contrastMode',
        'a11yes-bigCursor',
        'a11yes-highlighted',
        'a11yes-hideImages',
        'a11yes-blueFilter',
    ];

    const setStorageValue = (key, value) => {
        if (value === null || value === undefined || value === '') {
            window.localStorage.removeItem(key);
            return;
        }
        window.localStorage.setItem(key, String(value));
    };

    const adjustStep = (key, delta) => {
        const current = Number(window.localStorage.getItem(key) ?? 0);
        const next = Math.max(0, current + delta);
        window.localStorage.setItem(key, String(next));
    };

    const updateToggle = (key, node) => {
        const isActive =
            node.classList.contains('active')
            || node.getAttribute('aria-pressed') === 'true'
            || (node instanceof HTMLInputElement && node.checked);
        setStorageValue(key, isActive ? '1' : '0');
    };

    const applyReset = () => {
        window.localStorage.setItem('a11yes-reset', String(Date.now()));
        resetKeys.forEach((key) => window.localStorage.removeItem(key));
    };

    const removeInvertOption = () => {
        const invertInput = document.querySelector('.a11yes-window .a11yes-filter-invert');
        if (!invertInput) {
            return false;
        }
        const label = invertInput.closest('label');
        if (label) {
            label.remove();
            return true;
        }
        invertInput.remove();
        return true;
    };

    const applyConfig = () => {
        const a11yParams = parseJsonAttribute(a11yBtn.getAttribute('data-params'));
        cleanupA11yesUi();
        a11YesInit({
            openButtonClassname: 'a11yes-open',
            ...a11yParams,
        });
        if (!removeInvertOption()) {
            const observer = new MutationObserver(() => {
                if (removeInvertOption()) {
                    observer.disconnect();
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    };

    applyConfig();

    if (!globalThis.__a11yesMainStorageWriter) {
        globalThis.__a11yesMainStorageWriter = true;
        document.addEventListener('click', (event) => {
            const target = event.target instanceof Element ? event.target : null;
            if (!target) {
                return;
            }
            const root = target.closest('.a11yes-window');
            if (!root) {
                return;
            }
            const node = target.closest(
                'button, [role="button"], input[type="button"], input[type="radio"], input[type="checkbox"]'
            );
            if (!node) {
                return;
            }

            if (node.classList.contains('a11yes-font-increase')) {
                adjustStep(stepKeys.fontSize, 1);
                return;
            }
            if (node.classList.contains('a11yes-font-decrease')) {
                adjustStep(stepKeys.fontSize, -1);
                return;
            }
            if (node.classList.contains('a11yes-letter-spacing-increase')) {
                adjustStep(stepKeys.letterSpacing, 1);
                return;
            }
            if (node.classList.contains('a11yes-letter-spacing-decrease')) {
                adjustStep(stepKeys.letterSpacing, -1);
                return;
            }
            if (node.classList.contains('a11yes-font-name')) {
                const value =
                    node.getAttribute('value')
                    || node.getAttribute('data-value')
                    || (node.textContent || '').trim();
                setStorageValue('a11yes-fontType', value);
                return;
            }
            if (node.classList.contains('a11yes-filter-normal')) {
                setStorageValue('a11yes-contrastMode', 'standard');
                return;
            }
            if (node.classList.contains('a11yes-filter-contrast')) {
                setStorageValue('a11yes-contrastMode', 'high');
                return;
            }
            if (node.classList.contains('a11yes-filter-invert')) {
                setStorageValue('a11yes-contrastMode', 'invert');
                return;
            }
            if (node.classList.contains('a11yes-filter-monochrome')) {
                setStorageValue('a11yes-contrastMode', 'monochrome');
                return;
            }
            if (node.classList.contains('a11yes-reset')) {
                applyReset();
                return;
            }
            if (
                node.classList.contains('a11yes-cursor')
                || node.classList.contains('a11yes-highlight')
                || node.classList.contains('a11yes-images')
                || node.classList.contains('a11yes-bluefilter')
            ) {
                window.setTimeout(() => {
                    if (node.classList.contains('a11yes-cursor')) {
                        updateToggle('a11yes-bigCursor', node);
                    } else if (node.classList.contains('a11yes-highlight')) {
                        updateToggle('a11yes-highlighted', node);
                    } else if (node.classList.contains('a11yes-images')) {
                        updateToggle('a11yes-hideImages', node);
                    } else if (node.classList.contains('a11yes-bluefilter')) {
                        updateToggle('a11yes-blueFilter', node);
                    }
                }, 0);
            }
        });

        document.addEventListener('change', (event) => {
            const target = event.target instanceof Element ? event.target : null;
            if (!target || !target.closest('.a11yes-window')) {
                return;
            }
            if (target.classList.contains('a11yes-font-name')) {
                const value =
                    target.getAttribute('value')
                    || target.getAttribute('data-value')
                    || (target.textContent || '').trim();
                setStorageValue('a11yes-fontType', value);
                return;
            }
            if (target.classList.contains('a11yes-filter-normal')) {
                setStorageValue('a11yes-contrastMode', 'standard');
                return;
            }
            if (target.classList.contains('a11yes-filter-contrast')) {
                setStorageValue('a11yes-contrastMode', 'high');
                return;
            }
            if (target.classList.contains('a11yes-filter-invert')) {
                setStorageValue('a11yes-contrastMode', 'invert');
                return;
            }
            if (target.classList.contains('a11yes-filter-monochrome')) {
                setStorageValue('a11yes-contrastMode', 'monochrome');
            }
        });
    }

    const triggerA11yesButton = (matchers) => {
        const root = document.querySelector('.a11yes-window');
        if (!root) {
            return false;
        }

        const candidates = Array.from(
            root.querySelectorAll(
                'button, [role="button"], input[type="button"], input[type="radio"], input[type="checkbox"]'
            )
        );

        const byAria = matchers.aria
            ? candidates.find((node) => (node.getAttribute('aria-label') || '') === matchers.aria)
            : null;
        if (byAria) {
            byAria.click();
            return true;
        }

        if (matchers.text) {
            const byText = candidates.find(
                (node) => (node.textContent || '').trim() === matchers.text
            );
            if (byText) {
                byText.click();
                return true;
            }
        }

        if (matchers.className) {
            const byClass = candidates.find((node) => node.classList.contains(matchers.className));
            if (byClass) {
                byClass.click();
                return true;
            }
        }

        if (matchers.test) {
            const byTest = candidates.find((node) => matchers.test(node));
            if (byTest) {
                byTest.click();
                return true;
            }
        }

        return false;
    };

    globalThis.a11yesDebug = globalThis.a11yesDebug ?? {};
    globalThis.a11yesDebug.fontSizePlus = () =>
        triggerA11yesButton({
            aria: 'Increase size',
            text: 'Increase size',
            className: 'a11yes-plus',
            test: (node) =>
                (node.getAttribute('aria-label') || '').includes('Increase')
                || (node.getAttribute('aria-label') || '').includes('Schrift')
                || (node.textContent || '').includes('+'),
        });

    globalThis.a11yesDebug.findButtons = () => {
        const root = document.querySelector('.a11yes-window');
        if (!root) {
            return [];
        }
        return Array.from(
            root.querySelectorAll(
                'button, [role="button"], input[type="button"], input[type="radio"], input[type="checkbox"]'
            )
        ).map((node) => ({
            aria: node.getAttribute('aria-label'),
            text: (node.textContent || '').trim(),
            class: node.getAttribute('class'),
            type: node.getAttribute('type'),
        }));
    };

    globalThis.a11yesDebug._storageWatchers = globalThis.a11yesDebug._storageWatchers ?? [];
    if (!globalThis.a11yesDebug._storageHooked) {
        const originalSetItem = window.localStorage.setItem.bind(window.localStorage);
        const originalRemoveItem = window.localStorage.removeItem.bind(window.localStorage);

        window.localStorage.setItem = (itemKey, value) => {
            originalSetItem(itemKey, value);
            globalThis.a11yesDebug._storageWatchers.forEach((watcher) => {
                watcher(itemKey, window.localStorage.getItem(itemKey));
            });
        };

        window.localStorage.removeItem = (itemKey) => {
            originalRemoveItem(itemKey);
            globalThis.a11yesDebug._storageWatchers.forEach((watcher) => {
                watcher(itemKey, window.localStorage.getItem(itemKey));
            });
        };

        globalThis.a11yesDebug._storageHooked = true;
    }

    globalThis.a11yesDebug.watchStorage = (key, expectedValue, callback) => {
        const watcher = (changedKey, currentValue) => {
            if (changedKey !== key) {
                return;
            }
            if (expectedValue === undefined || currentValue === String(expectedValue)) {
                callback(currentValue);
            }
        };

        globalThis.a11yesDebug._storageWatchers.push(watcher);
        const onStorage = (event) => {
            if (event.key === key) {
                watcher(event.key, event.newValue);
            }
        };
        window.addEventListener('storage', onStorage);

        return () => {
            globalThis.a11yesDebug._storageWatchers =
                globalThis.a11yesDebug._storageWatchers.filter((entry) => entry !== watcher);
            window.removeEventListener('storage', onStorage);
        };
    };

    window.addEventListener('storage', (event) => {
        if (!event.key || !event.key.startsWith('a11yes-')) {
            return;
        }
        if (document.querySelector('.a11yes-window')) {
            return;
        }
        applyConfig();
    });
};

void initA11yes().catch(() => {});
