<?php

namespace NITSAN\NsFriendlycaptcha\ViewHelpers\Form;

use NITSAN\NsFriendlycaptcha\Services\CaptchaService;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

class RecaptchaViewHelper extends AbstractFormFieldViewHelper
{
    protected CaptchaService $captchaService;

    public function __construct(CaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
        parent::__construct();
    }

    public function render(): string
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        if(version_compare(TYPO3_version, '10.0.0', '<=')){
            $lang = $GLOBALS['TYPO3_REQUEST']->getAttribute('language')->getTwoLetterIsoCode();
        } else {
            $lang = $GLOBALS['TSFE']->language->getTwoLetterIsoCode() ? $GLOBALS['TSFE']->language->getTwoLetterIsoCode() : 'en';
        }
        $container = $this->templateVariableContainer;
        $container->add('configuration', $this->captchaService->getConfiguration());
        $container->add('showCaptcha', $this->captchaService->getShowCaptcha());
        $container->add('name', $name);
        $container->add('lang', $lang);

        $content = $this->renderChildren();

        $container->remove('name');
        $container->remove('showCaptcha');
        $container->remove('configuration');

        return $content;
    }
}
