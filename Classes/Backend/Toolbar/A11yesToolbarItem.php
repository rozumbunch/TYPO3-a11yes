<?php

declare(strict_types=1);

namespace Rozumbunch\A11yes\Backend\Toolbar;

use Rozumbunch\A11yes\Configuration\BackendConfiguration;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;

final class A11yesToolbarItem implements ToolbarItemInterface
{
    public function __construct(
        private readonly A11yesToolbarButtonRenderer $renderer,
        private readonly BackendConfiguration $backendConfiguration
    ) {
    }

    public function checkAccess(): bool
    {
        return $this->backendConfiguration->isBackendEnabled();
    }

    public function getItem(): string
    {
        return $this->renderer->render();
    }

    public function getDropDown(): string
    {
        return '';
    }

    public function hasDropDown(): bool
    {
        return false;
    }

    public function getAdditionalAttributes(): array
    {
        return [];
    }

    public function getIndex(): int
    {
        return 5;
    }

}
