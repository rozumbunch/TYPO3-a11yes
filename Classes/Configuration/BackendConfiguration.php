<?php

declare(strict_types=1);

namespace Rozumbunch\A11yes\Configuration;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final class BackendConfiguration
{
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    public function isBackendEnabled(): bool
    {
        try {
            $config = $this->extensionConfiguration->get('a11yes');
        } catch (\Throwable) {
            return true;
        }

        if (!is_array($config)) {
            return true;
        }

        return (bool)($config['backendEnabled'] ?? true);
    }
}
