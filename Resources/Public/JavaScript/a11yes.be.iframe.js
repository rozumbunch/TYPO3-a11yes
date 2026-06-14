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

globalThis.a11yesDebug = globalThis.a11yesDebug ?? {};
globalThis.a11yesDebug.findButtons = globalThis.a11yesDebug.findButtons ?? (() => []);

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

    const applyConfig = () => {
        const a11yParams = parseJsonAttribute(a11yBtn.getAttribute('data-params'));
        cleanupA11yesUi();
        a11YesInit({
            openButtonClassname: 'a11yes-open',
            ...a11yParams,
        });
    };

    applyConfig();

    const openPopup = () => {
        const root = document.querySelector('.a11yes-window');
        if (root) {
            return Promise.resolve(root);
        }

        a11yBtn.click();
        return new Promise((resolve) => {
            window.setTimeout(() => {
                resolve(document.querySelector('.a11yes-window'));
            }, 80);
        });
    };

    const getLabel = (node) =>
        (node.getAttribute('aria-label') || node.textContent || '').trim().toLowerCase();

    const getCandidates = (root) =>
        Array.from(
            root.querySelectorAll(
                'button, [role="button"], input[type="button"], input[type="radio"], input[type="checkbox"]'
            )
        );

    const clickBy = async (matcher, retry = 0) => {
        const root = await openPopup();
        if (!root) {
            return false;
        }
        const candidates = getCandidates(root);
        const match = candidates.find((node) => matcher(node, getLabel(node)));
        if (match) {
            if (match instanceof HTMLInputElement && match.type === 'radio') {
                match.checked = true;
                match.dispatchEvent(new Event('change', { bubbles: true }));
                window.setTimeout(() => {
                    if (!match.checked) {
                        match.click();
                    }
                }, 0);
                return true;
            }
            match.click();
            return true;
        }
        if (retry < 1) {
            return new Promise((resolve) => {
                window.setTimeout(async () => {
                    resolve(await clickBy(matcher, retry + 1));
                }, 150);
            });
        }
        return false;
    };

    const clickFontSizePlus = () =>
        clickBy((node, label) =>
            label.includes('increase size of fonts')
            || node.classList.contains('a11yes-font-increase')
        );
    const clickFontSizeMinus = () =>
        clickBy((node, label) =>
            label.includes('decrease size of fonts')
            || node.classList.contains('a11yes-font-decrease')
        );
    const clickLetterSpacingPlus = () =>
        clickBy((node, label) =>
            label.includes('increase size of letter spacing')
            || node.classList.contains('a11yes-letter-spacing-increase')
        );
    const clickLetterSpacingMinus = () =>
        clickBy((node, label) =>
            label.includes('decrease size of letter spacing')
            || node.classList.contains('a11yes-letter-spacing-decrease')
        );

    const clickFontType = (value) =>
        clickBy((node, label) =>
            node.classList.contains('a11yes-font-name')
            && (node.getAttribute('value') || '').toLowerCase() === String(value).toLowerCase()
        );

    const getCheckedContrast = () => {
        const root = document.querySelector('.a11yes-window');
        if (!root) {
            return null;
        }
        if (root.querySelector('.a11yes-filter-normal:checked')) {
            return 'standard';
        }
        if (root.querySelector('.a11yes-filter-contrast:checked')) {
            return 'high';
        }
        if (root.querySelector('.a11yes-filter-invert:checked')) {
            return 'invert';
        }
        if (root.querySelector('.a11yes-filter-monochrome:checked')) {
            return 'monochrome';
        }
        return null;
    };

    const clickContrastMode = async (mode) => {
        const clickTarget = (target) =>
            clickBy((node) => {
                switch (target) {
                case 'standard':
                    return node.classList.contains('a11yes-filter-normal');
                case 'high':
                    return node.classList.contains('a11yes-filter-contrast');
                case 'invert':
                    return node.classList.contains('a11yes-filter-invert');
                case 'monochrome':
                    return node.classList.contains('a11yes-filter-monochrome');
                default:
                    return false;
                }
            });

        const current = getCheckedContrast();
        if (mode === 'monochrome' && current === 'standard') {
            await clickTarget('invert');
            return new Promise((resolve) => {
                window.setTimeout(async () => {
                    resolve(await clickTarget('monochrome'));
                }, 120);
            });
        }

        return clickTarget(mode);
    };

    const clickOtherFunction = (key) =>
        clickBy((node, label) => label.includes(key));

    const clickReset = () =>
        clickBy((node, label) =>
            node.classList.contains('a11yes-reset')
            || label.includes('reset')
        );
    globalThis.a11yesDebug.findButtons = () => {
        const root = document.querySelector('.a11yes-window');
        if (!root) {
            return [];
        }
        return getCandidates(root).map((node) => ({
            aria: node.getAttribute('aria-label'),
            text: (node.textContent || '').trim(),
            class: node.getAttribute('class'),
            type: node.getAttribute('type'),
        }));
    };

    const simulateStepChange = (key, onIncrease, onDecrease) => {
        let lastValue = Number(window.localStorage.getItem(key) ?? 0);
        const handleChange = () => {
            const current = Number(window.localStorage.getItem(key) ?? 0);
            if (current === lastValue) {
                return;
            }
            const diff = current - lastValue;
            lastValue = current;
            if (diff > 0) {
                for (let i = 0; i < diff; i += 1) {
                    onIncrease();
                }
            } else if (diff < 0) {
                for (let i = 0; i < Math.abs(diff); i += 1) {
                    onDecrease();
                }
            }
        };

        window.addEventListener('storage', (event) => {
            if (event.key === key) {
                handleChange();
            }
        });

        globalThis.__a11yesStepWatchers = globalThis.__a11yesStepWatchers ?? [];
        globalThis.__a11yesStepWatchers.push({ key, handleChange });

        if (!window.localStorage.__a11yesStepHooked) {
            const originalSetItem = window.localStorage.setItem.bind(window.localStorage);
            window.localStorage.setItem = (itemKey, value) => {
                originalSetItem(itemKey, value);
                globalThis.__a11yesStepWatchers.forEach((watcher) => {
                    if (watcher.key === itemKey) {
                        watcher.handleChange();
                    }
                });
            };
            window.localStorage.__a11yesStepHooked = true;
        }
    };

    const watchValue = (key, handler) => {
        let lastValue = window.localStorage.getItem(key);
        const handleChange = () => {
            const current = window.localStorage.getItem(key);
            if (current === lastValue) {
                return;
            }
            lastValue = current;
            handler(current);
        };

        window.addEventListener('storage', (event) => {
            if (event.key === key) {
                handleChange();
            }
        });

        globalThis.__a11yesValueWatchers = globalThis.__a11yesValueWatchers ?? [];
        globalThis.__a11yesValueWatchers.push({ key, handleChange });

        if (!window.localStorage.__a11yesValueHooked) {
            const originalSetItem = window.localStorage.setItem.bind(window.localStorage);
            window.localStorage.setItem = (itemKey, value) => {
                originalSetItem(itemKey, value);
                globalThis.__a11yesValueWatchers.forEach((watcher) => {
                    if (watcher.key === itemKey) {
                        watcher.handleChange();
                    }
                });
            };
            window.localStorage.__a11yesValueHooked = true;
        }
    };

    simulateStepChange('a11yes-fontSizeStepsCount', clickFontSizePlus, clickFontSizeMinus);
    simulateStepChange('a11yes-letterSpacingStepsCount', clickLetterSpacingPlus, clickLetterSpacingMinus);

    watchValue('a11yes-fontType', (value) => {
        if (value) {
            clickFontType(value);
        }
    });
    watchValue('a11yes-contrastMode', (value) => {
        if (value) {
            void clickContrastMode(value);
        }
    });
    const toggleSwitch = (className, value) => {
        if (value === null) {
            return;
        }
        const shouldEnable = value === '1' || value === 'true';
        clickBy((node) => {
            if (!node.classList.contains(className)) {
                return false;
            }
            const isActive = node.classList.contains('active') || node.getAttribute('aria-pressed') === 'true';
            return isActive !== shouldEnable;
        });
    };

    watchValue('a11yes-bigCursor', (value) => {
        toggleSwitch('a11yes-cursor', value);
    });
    watchValue('a11yes-highlighted', (value) => {
        toggleSwitch('a11yes-highlight', value);
    });
    watchValue('a11yes-hideImages', (value) => {
        toggleSwitch('a11yes-images', value);
    });
    watchValue('a11yes-blueFilter', (value) => {
        toggleSwitch('a11yes-bluefilter', value);
    });
    let resettingStorage = false;
    const resetStorageDefaults = () => {
        resettingStorage = true;
        [
            'a11yes-fontSizeStepsCount',
            'a11yes-letterSpacingStepsCount',
            'a11yes-fontType',
            'a11yes-contrastMode',
            'a11yes-bigCursor',
            'a11yes-highlighted',
            'a11yes-hideImages',
            'a11yes-blueFilter',
        ].forEach((key) => window.localStorage.removeItem(key));
        resettingStorage = false;
    };

    watchValue('a11yes-reset', (value) => {
        if (value !== null) {
            clickReset();
            resetStorageDefaults();
        }
    });
};

void initA11yes().catch(() => {});
