const ensureJqueryShim = () => {
    if (globalThis.$ && globalThis.jQuery) {
        return;
    }

    const shim = (selector) => {
        const elements = Array.from(document.querySelectorAll(selector));

        return {
            prop: (name, value) => {
                elements.forEach((element) => {
                    if (name in element) {
                        element[name] = value;
                    } else {
                        element.setAttribute(name, String(value));
                    }
                });

                return elements;
            },
        };
    };

    globalThis.$ = globalThis.$ ?? shim;
    globalThis.jQuery = globalThis.jQuery ?? shim;
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

const applyThemeVariables = (theme) => {
    if (!theme || typeof theme !== 'object') {
        return;
    }
    const enabled = Boolean(theme.enabled);

    const root = document.documentElement;
    const colors = theme.colors || {};
    const keys = [
        '--a11yes-foreground',
        '--a11yes-secondary-foreground',
        '--a11yes-background',
        '--a11yes-secondary-background',
        '--a11yes-section-border',
        '--a11yes-switch-background',
    ];

    if (enabled) {
        keys.forEach((key) => {
            const value = colors[key];
            if (value) {
                root.style.setProperty(key, value);
            }
        });
        return;
    }

    keys.forEach((key) => root.style.removeProperty(key));
};

document.addEventListener('DOMContentLoaded', async () => {
    ensureJqueryShim();

    const { a11YesInit } = await import('../Contrib/a11yes.min.js');
    const a11yOpenButton = document.querySelector('.a11yes-open');
    const a11yParameters = {};
    const hasCustomButton = Boolean(a11yOpenButton);

    const configElement = document.getElementById('a11yes-config');
    if (configElement) {
        Object.assign(a11yParameters, parseJsonAttribute(configElement.textContent));
    }

    if (a11yOpenButton) {
        const paramsAttribute = a11yOpenButton.getAttribute('data-params');
        if (paramsAttribute) {
            try {
                Object.assign(a11yParameters, JSON.parse(paramsAttribute));
            } catch (error) {
                console.warn('a11Yes: data-params ist kein gültiges JSON.', error);
                Object.assign(a11yParameters, parseJsonAttribute(paramsAttribute));
            }
        }
    }

    applyThemeVariables(a11yParameters.theme);

    if (!hasCustomButton) {
        a11YesInit();
    } else {
        a11YesInit({
            openButtonClassname: 'a11yes-open',
            ...a11yParameters,
        });
    }
});
