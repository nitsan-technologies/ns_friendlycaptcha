<?php

declare(strict_types=1);

namespace NITSAN\NsFriendlycaptcha\ViewHelpers\Form;

use NITSAN\NsFriendlycaptcha\Services\CaptchaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

class RecaptchaViewHelper extends AbstractFormFieldViewHelper
{
    protected CaptchaService $captchaService;

    public function __construct(CaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
        parent::__construct();
    }

    /**
     * @throws ContentRenderingException
     */
    public function render(): string
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        if($GLOBALS['TSFE']) {
            $contents = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $currentLang = $contents->getRequest()->getAttributes();
            $lang = $currentLang['language']->getLocale()->getLanguageCode() ? $currentLang['language']->getLocale()->getLanguageCode() : 'en';
            $container = $this->templateVariableContainer;
            $container->add('configuration', $this->captchaService->getConfiguration());
            $container->add('showCaptcha', $this->captchaService->getShowCaptcha());
            $container->add('name', $name);
            $container->add('lang', $lang);
            $content = $this->renderChildren();

            $container->remove('name');
            $container->remove('showCaptcha');
            $container->remove('configuration');
        } else {
            $content = $this->renderChildren();
        }
        return $content;
    }
}
