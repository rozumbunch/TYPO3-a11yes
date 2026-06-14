<?php

declare(strict_types=1);

namespace Rozumbunch\A11yes\Configuration\Resolver;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;

final class A11yesResolver
{
    private const MODE_SITE_SETTINGS = 'siteSettings';
    private const MODE_TYPO_SCRIPT = 'typoScript';

    /**
     * @var list<string>
     */
    private const SITE_SETTING_KEYS = [
        'a11yes.mode',
        'a11yes.fontSize.enabled',
        'a11yes.fontSize.step',
        'a11yes.fontSize.maxSteps',
        'a11yes.fontSize.minSteps',
        'a11yes.letterSpacing.enabled',
        'a11yes.letterSpacing.step',
        'a11yes.letterSpacing.maxSteps',
        'a11yes.letterSpacing.minSteps',
        'a11yes.fontsJson',
        'a11yes.contrast.enabled',
        'a11yes.contrast.highlightColor',
        'a11yes.theme.enabled',
        'a11yes.theme.foreground',
        'a11yes.theme.secondaryForeground',
        'a11yes.theme.background',
        'a11yes.theme.secondaryBackground',
        'a11yes.theme.sectionBorder',
        'a11yes.theme.switchBackground',
        'a11yes.otherFunctions.enabled',
        'a11yes.icons.enabled',
        'a11yes.hideFixedButton',
        'a11yes.linksJson',
        'a11yes.translationsPathPattern',
    ];

    /**
     * @var array<string, string>
     */
    private const DEFAULT_FONTS = [
        'Open Sans' => 'Open Sans, sans-serif',
        'Times New Roman' => 'Times New Roman, serif',
    ];

    /**
     * @var array<string, string>
     */
    private const DEFAULT_THEME_COLORS = [
        '--a11yes-foreground' => '#3B1E5E',
        '--a11yes-secondary-foreground' => '#6B5B8A',
        '--a11yes-background' => '#F3EDFA',
        '--a11yes-secondary-background' => '#FFFFFF',
        '--a11yes-section-border' => '#DCD0EC',
        '--a11yes-switch-background' => '#E5DCF0',
    ];

    /**
     * @return array<string, mixed>
     */
    public function resolve(?ServerRequestInterface $request = null): array
    {
        $request ??= $this->resolveGlobalRequest();

        $settings = $this->resolveSiteSettings($request);
        $typoScript = $this->getTypoScriptSettings($request);

        $useSiteSettings = $this->resolveUseSiteSettings($typoScript, $settings);
        $language = $this->resolveTypo3Language($request);

        $fontsJsonDefault = json_encode(self::DEFAULT_FONTS, JSON_UNESCAPED_UNICODE) ?: '{}';
        $fontsJson = $useSiteSettings
            ? $this->getSettingString($settings, 'a11yes.fontsJson', $fontsJsonDefault)
            : $this->getTsString($typoScript, 'fontsJson', $fontsJsonDefault);

        $pattern = $useSiteSettings
            ? $this->getSettingString($settings, 'a11yes.translationsPathPattern', '../translations/{language}.json')
            : $this->getTsString($typoScript, 'translationsPathPattern', '../translations/{language}.json');

        return [
            'currentLanguage' => $language,
            'fontSize' => $useSiteSettings
                ? $this->getSettingBool($settings, 'a11yes.fontSize.enabled', true)
                : $this->getTsBool($typoScript, 'fontSizeEnabled', true),
            'fontSizeStep' => $useSiteSettings
                ? $this->getSettingInt($settings, 'a11yes.fontSize.step', 2)
                : $this->getTsInt($typoScript, 'fontSizeStep', 2),
            'fontSizeMaxSteps' => $useSiteSettings
                ? $this->getSettingInt($settings, 'a11yes.fontSize.maxSteps', 5)
                : $this->getTsInt($typoScript, 'fontSizeMaxSteps', 5),
            'fontSizeMinSteps' => $useSiteSettings
                ? $this->getSettingInt($settings, 'a11yes.fontSize.minSteps', 0)
                : $this->getTsInt($typoScript, 'fontSizeMinSteps', 0),
            'letterSpacing' => $useSiteSettings
                ? $this->getSettingBool($settings, 'a11yes.letterSpacing.enabled', true)
                : $this->getTsBool($typoScript, 'letterSpacingEnabled', true),
            'letterSpacingStep' => $useSiteSettings
                ? $this->getSettingInt($settings, 'a11yes.letterSpacing.step', 1)
                : $this->getTsInt($typoScript, 'letterSpacingStep', 1),
            'maxLetterSpacingSteps' => $useSiteSettings
                ? $this->getSettingInt($settings, 'a11yes.letterSpacing.maxSteps', 5)
                : $this->getTsInt($typoScript, 'letterSpacingMaxSteps', 5),
            'minLetterSpacingSteps' => $useSiteSettings
                ? $this->getSettingInt($settings, 'a11yes.letterSpacing.minSteps', 0)
                : $this->getTsInt($typoScript, 'letterSpacingMinSteps', 0),
            'contrast' => $useSiteSettings
                ? $this->getSettingBool($settings, 'a11yes.contrast.enabled', true)
                : $this->getTsBool($typoScript, 'contrastEnabled', true),
            'highLightColor' => $useSiteSettings
                ? $this->getSettingString($settings, 'a11yes.contrast.highlightColor', 'blue')
                : $this->getTsString($typoScript, 'highlightColor', 'blue'),
            'fonts' => $this->parseFontsJson($fontsJson),
            'otherFunctions' => $useSiteSettings
                ? $this->getSettingBool($settings, 'a11yes.otherFunctions.enabled', true)
                : $this->getTsBool($typoScript, 'otherFunctionsEnabled', true),
            'links' => $this->resolveLinks($typoScript, $settings, $useSiteSettings),
            'translations' => str_replace('{language}', $language, $pattern),
            'icons' => $useSiteSettings
                ? $this->getSettingBool($settings, 'a11yes.icons.enabled', true)
                : $this->getTsBool($typoScript, 'iconsEnabled', true),
            'showFixedButton' => $useSiteSettings
                ? !$this->getSettingBool($settings, 'a11yes.hideFixedButton', false)
                : $this->getTsBool($typoScript, 'showFixedButton', true),
            'theme' => [
                'enabled' => $useSiteSettings
                    ? $this->getSettingBool($settings, 'a11yes.theme.enabled', false)
                    : $this->getTsBool($typoScript, 'themeEnabled', false),
                'colors' => $this->resolveThemeColors($settings, $typoScript, $useSiteSettings),
            ],
        ];
    }

    private function resolveGlobalRequest(): ?ServerRequestInterface
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        return $request instanceof ServerRequestInterface ? $request : null;
    }

    private function resolveSiteSettings(?ServerRequestInterface $request): ?SiteSettings
    {
        $site = $request?->getAttribute('site');
        if (!is_object($site) || !method_exists($site, 'getSettings')) {
            return null;
        }

        $settings = $site->getSettings();

        return $settings instanceof SiteSettings ? $settings : null;
    }

    private function resolveTypo3Language(?ServerRequestInterface $request): string
    {
        $language = $request?->getAttribute('language');
        if ($language instanceof SiteLanguage) {
            $locale = $language->getLocale();
            if ($locale instanceof Locale) {
                return $this->normalizeLanguageCode($locale->getLanguageCode());
            }
            if (is_string($locale) && $locale !== '') {
                return $this->normalizeLanguageCode($locale);
            }

            return $this->normalizeLanguageCode($language->getTypo3Language());
        }

        return 'de';
    }

    private function normalizeLanguageCode(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'de';
        }

        $value = str_replace('-', '_', $value);
        $parts = preg_split('/[_.@]/', $value);
        $code = $parts !== false && $parts !== [] ? $parts[0] : $value;

        return strtolower($code);
    }

    /**
     * @param array<string, mixed> $typoScript
     */
    private function resolveUseSiteSettings(array $typoScript, ?SiteSettings $settings): bool
    {
        $siteMode = $settings !== null ? $this->getSettingString($settings, 'a11yes.mode', '') : '';
        if ($siteMode === self::MODE_SITE_SETTINGS || $siteMode === self::MODE_TYPO_SCRIPT) {
            return $siteMode === self::MODE_SITE_SETTINGS;
        }

        // Legacy fallback for projects that previously used mode in TypoScript.
        $tsMode = $this->getTsString($typoScript, 'mode', '');
        if ($tsMode === self::MODE_SITE_SETTINGS || $tsMode === self::MODE_TYPO_SCRIPT) {
            return $tsMode === self::MODE_SITE_SETTINGS;
        }

        return $this->hasA11yesSiteSettings($settings);
    }

    private function hasA11yesSiteSettings(?SiteSettings $settings): bool
    {
        if ($settings === null) {
            return false;
        }

        foreach (self::SITE_SETTING_KEYS as $key) {
            $value = $settings->get($key, null);
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function getSettingBool(?SiteSettings $settings, string $key, bool $default): bool
    {
        if ($settings === null) {
            return $default;
        }
        $value = $settings->get($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value !== 0;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    private function getSettingInt(?SiteSettings $settings, string $key, int $default): int
    {
        if ($settings === null) {
            return $default;
        }
        $value = $settings->get($key, $default);
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int)$value;
        }

        return $default;
    }

    private function getSettingString(?SiteSettings $settings, string $key, string $default): string
    {
        if ($settings === null) {
            return $default;
        }
        $value = $settings->get($key, $default);

        return $value !== null && $value !== '' ? (string)$value : $default;
    }

    /**
     * @param array<string, mixed> $typoScript
     */
    private function getTsString(array $typoScript, string $key, string $default): string
    {
        $value = $typoScript[$key] ?? null;
        $value = is_string($value) || is_numeric($value) ? trim((string)$value) : '';

        return $value !== '' ? $value : $default;
    }

    /**
     * @param array<string, mixed> $typoScript
     */
    private function getTsInt(array $typoScript, string $key, int $default): int
    {
        $value = $typoScript[$key] ?? null;
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int)$value;
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $typoScript
     */
    private function getTsBool(array $typoScript, string $key, bool $default): bool
    {
        $value = $typoScript[$key] ?? null;
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value !== 0;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * @return list<array{0: string, 1: string, 2?: string}>
     */
    private function parseLinksJson(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [['Weitere Informationen', 'https://www.a11yes.com/', '_blank']];
        }
        if (!is_array($decoded)) {
            return [['Weitere Informationen', 'https://www.a11yes.com/', '_blank']];
        }

        $links = [];
        foreach ($decoded as $row) {
            if (!is_array($row) || count($row) < 2) {
                continue;
            }
            $label = (string)($row[0] ?? '');
            $url = (string)($row[1] ?? '');
            if ($label === '' || $url === '') {
                continue;
            }
            $target = isset($row[2]) ? (string)$row[2] : '_self';
            $links[] = [$label, $url, $target];
        }

        return $links !== [] ? $links : [['Weitere Informationen', 'https://www.a11yes.com/', '_blank']];
    }

    /**
     * @return array<string, string>
     */
    private function parseFontsJson(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return self::DEFAULT_FONTS;
        }
        if (!is_array($decoded)) {
            return self::DEFAULT_FONTS;
        }

        $fonts = [];
        foreach ($decoded as $name => $css) {
            if (!is_string($name) || !is_string($css) || $name === '' || $css === '') {
                continue;
            }
            $fonts[$name] = $css;
        }

        return $fonts !== [] ? $fonts : self::DEFAULT_FONTS;
    }

    /**
     * @param array<string, mixed> $typoScript
     * @return array<string, string>
     */
    private function resolveThemeColors(?SiteSettings $settings, array $typoScript, bool $useSiteSettings): array
    {
        if ($useSiteSettings) {
            return [
                '--a11yes-foreground' => $this->getSettingString(
                    $settings,
                    'a11yes.theme.foreground',
                    self::DEFAULT_THEME_COLORS['--a11yes-foreground']
                ),
                '--a11yes-secondary-foreground' => $this->getSettingString(
                    $settings,
                    'a11yes.theme.secondaryForeground',
                    self::DEFAULT_THEME_COLORS['--a11yes-secondary-foreground']
                ),
                '--a11yes-background' => $this->getSettingString(
                    $settings,
                    'a11yes.theme.background',
                    self::DEFAULT_THEME_COLORS['--a11yes-background']
                ),
                '--a11yes-secondary-background' => $this->getSettingString(
                    $settings,
                    'a11yes.theme.secondaryBackground',
                    self::DEFAULT_THEME_COLORS['--a11yes-secondary-background']
                ),
                '--a11yes-section-border' => $this->getSettingString(
                    $settings,
                    'a11yes.theme.sectionBorder',
                    self::DEFAULT_THEME_COLORS['--a11yes-section-border']
                ),
                '--a11yes-switch-background' => $this->getSettingString(
                    $settings,
                    'a11yes.theme.switchBackground',
                    self::DEFAULT_THEME_COLORS['--a11yes-switch-background']
                ),
            ];
        }

        return [
            '--a11yes-foreground' => $this->getTsString(
                $typoScript,
                'themeForeground',
                self::DEFAULT_THEME_COLORS['--a11yes-foreground']
            ),
            '--a11yes-secondary-foreground' => $this->getTsString(
                $typoScript,
                'themeSecondaryForeground',
                self::DEFAULT_THEME_COLORS['--a11yes-secondary-foreground']
            ),
            '--a11yes-background' => $this->getTsString(
                $typoScript,
                'themeBackground',
                self::DEFAULT_THEME_COLORS['--a11yes-background']
            ),
            '--a11yes-secondary-background' => $this->getTsString(
                $typoScript,
                'themeSecondaryBackground',
                self::DEFAULT_THEME_COLORS['--a11yes-secondary-background']
            ),
            '--a11yes-section-border' => $this->getTsString(
                $typoScript,
                'themeSectionBorder',
                self::DEFAULT_THEME_COLORS['--a11yes-section-border']
            ),
            '--a11yes-switch-background' => $this->getTsString(
                $typoScript,
                'themeSwitchBackground',
                self::DEFAULT_THEME_COLORS['--a11yes-switch-background']
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getTypoScriptSettings(?ServerRequestInterface $request): array
    {
        $frontendTypoScript = $request?->getAttribute('frontend.typoscript');
        if ($frontendTypoScript instanceof FrontendTypoScript) {
            return $this->getA11yesSettingsFromTypoScriptSetup($frontendTypoScript->getSetupArray());
        }

        // Fallback old method
        $tsfe = $GLOBALS['TSFE'] ?? null;
        if (!is_object($tsfe) || !isset($tsfe->tmpl) || !is_object($tsfe->tmpl) || !isset($tsfe->tmpl->setup) || !is_array($tsfe->tmpl->setup)) {
            return [];
        }

        return $this->getA11yesSettingsFromTypoScriptSetup($tsfe->tmpl->setup);
    }

    /**
     * @param array<string, mixed> $setup
     * @return array<string, mixed>
     */
    private function getA11yesSettingsFromTypoScriptSetup(array $setup): array
    {
        $plugin = $setup['plugin.'] ?? null;
        if (!is_array($plugin)) {
            return [];
        }

        $tx = $plugin['tx_a11yes.'] ?? null;
        if (!is_array($tx)) {
            return [];
        }

        $settings = $tx['settings.'] ?? null;

        return is_array($settings) ? $settings : [];
    }

    /**
     * @param array<string, mixed> $typoScript
     * @return list<array{0: string, 1: string, 2?: string}>
     */
    private function resolveLinks(array $typoScript, ?SiteSettings $siteSettings, bool $useSiteSettings): array
    {
        if (!$useSiteSettings) {
            return $this->parseLinksJson(
                $this->getTsString($typoScript, 'linksJson', '[["More information","https://www.a11yes.com/","_blank"]]')
            );
        }

        return $this->parseLinksJson(
            $this->getSettingString($siteSettings, 'a11yes.linksJson', '[["Weitere Informationen","https://www.a11yes.com/","_blank"]]')
        );
    }
}
