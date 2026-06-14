<?php

declare(strict_types=1);

namespace Rozumbunch\A11yes\ViewHelpers;

use Rozumbunch\A11yes\Configuration\Resolver\A11yesResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to generate complete A11yes button with configuration.
 * Values are resolved from Site Settings or TypoScript constants.
 */
class A11yesButtonViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @return string
     */
    public function render(): string
    {
        $config = GeneralUtility::makeInstance(A11yesResolver::class)->resolve();

        $jsonConfig = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $jsonConfig = htmlspecialchars((string)$jsonConfig, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $svgIcon = $this->renderChildren();

        return sprintf(
            '<button class="a11yes-open" aria-label="Accessibility menu" data-params=\'%s\'>%s</button>',
            $jsonConfig,
            $svgIcon
        );
    }
}
