<?php

declare(strict_types=1);

namespace Rozumbunch\A11yes\Backend\Toolbar;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class A11yesToolbarButtonRenderer
{
    private const BACKEND_CONFIG_PATH = 'EXT:a11yes/Configuration/Backend/a11yes.yaml';

    public const TOOLBAR_BUTTON_CLASS = 'a11yes-open';

    public function __construct(
        private readonly PageRenderer $pageRenderer,
        private readonly IconFactory $iconFactory
    ) {
    }

    public function render(): string
    {
        $this->pageRenderer->addCssFile('EXT:a11yes/Resources/Public/Contrib/a11yes.min.css');
        $this->pageRenderer->addCssFile(
            'EXT:a11yes/Resources/Public/Css/backend.css',
            'stylesheet',
            'all',
            '',
            false,
            false,
            '',
            true
        );
        $this->pageRenderer->addJsFile('EXT:a11yes/Resources/Public/JavaScript/a11yes.be.js', 'module');

        $config = $this->resolveBackendConfig();
        $config['currentLanguage'] = 'de';
        $jsonConfig = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $jsonConfig = htmlspecialchars((string)$jsonConfig, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $iconMarkup = $this->iconFactory
            ->getIcon('a11yes-toolbar', Icon::SIZE_SMALL)
            ->render(SvgIconProvider::MARKUP_IDENTIFIER_INLINE);

        return sprintf(
            '<a href="#" class="toolbar-item-link %s"'
            . ' title="Accessibility" aria-label="Accessibility" data-params=\'%s\'>'
            . '<span class="toolbar-item-icon" title="Accessibility">%s</span>'
            . '<span class="toolbar-item-title">Accessibility</span>'
            . '</a>',
            self::TOOLBAR_BUTTON_CLASS,
            $jsonConfig,
            $iconMarkup
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveBackendConfig(): array
    {
        $filePath = GeneralUtility::getFileAbsFileName(self::BACKEND_CONFIG_PATH);
        if ($filePath === '' || !is_file($filePath)) {
            return [];
        }

        try {
            $data = Yaml::parseFile($filePath);
        } catch (\Throwable) {
            return [];
        }

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }
}
