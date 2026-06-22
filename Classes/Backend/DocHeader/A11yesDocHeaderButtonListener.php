<?php

declare(strict_types=1);

namespace Rozumbunch\A11yes\Backend\DocHeader;

use Rozumbunch\A11yes\Configuration\BackendConfiguration;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class A11yesDocHeaderButtonListener
{
    private const BACKEND_CONFIG_PATH = 'EXT:a11yes/Configuration/Backend/a11yes.yaml';

    public function __construct(
        private readonly PageRenderer $pageRenderer,
        private readonly IconFactory $iconFactory,
        private readonly BackendConfiguration $backendConfiguration
    ) {
    }

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        if (!$this->backendConfiguration->isBackendEnabled()) {
            return;
        }

        $config = $this->resolveBackendConfig();
        $jsonConfig = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->pageRenderer->addCssFile('EXT:a11yes/Resources/Public/Contrib/a11yes.min.css');
        $this->pageRenderer->addCssFile('EXT:a11yes/Resources/Public/Css/backend.css');
        $this->pageRenderer->addJsFile('EXT:a11yes/Resources/Public/JavaScript/a11yes.be.iframe.js', 'module');

        $buttonBar = $event->getButtonBar();
        $button = $buttonBar->makeLinkButton()
            ->setTitle('Accessibility')
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('a11yes-toolbar', IconSize::SMALL))
            ->setHref('#')
            ->setClasses('a11yes-open a11yes-docheader-button')
            ->setDataAttributes(['params' => (string)$jsonConfig]);

        $buttons = $event->getButtons();
        $buttons[ButtonBar::BUTTON_POSITION_RIGHT][50][] = $button;
        $event->setButtons($buttons);
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

        return is_array($data) ? $data : [];
    }
}
