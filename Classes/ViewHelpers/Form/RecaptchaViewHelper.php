<?php

declare(strict_types=1);

namespace NITSAN\NsFriendlycaptcha\ViewHelpers\Form;

use NITSAN\NsFriendlycaptcha\Services\CaptchaService;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

class RecaptchaViewHelper extends AbstractFormFieldViewHelper
{
    public function __construct(protected CaptchaService $captchaService)
    {
        parent::__construct();
    }

    public function render(): string
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);

        $lang = 'en';
        $request = $this->getRequest();
        if ($request !== null) {
            $language = $request->getAttribute('language');
            if ($language !== null) {
                $lang = $language->getLocale()->getLanguageCode() ?: 'en';
            }
        }

        $container = $this->templateVariableContainer;
        $container->add('configuration', $this->captchaService->getConfiguration());
        $container->add('showCaptcha', $this->captchaService->getShowCaptcha());
        $container->add('startMode', $this->captchaService->getStartMode());
        $container->add('puzzleEndpoint', $this->captchaService->getPuzzleEndpoint());
        $container->add('name', $name);
        $container->add('lang', $lang);

        $content = $this->renderChildren();

        $container->remove('puzzleEndpoint');
        $container->remove('startMode');
        $container->remove('lang');
        $container->remove('name');
        $container->remove('showCaptcha');
        $container->remove('configuration');

        return (string)$content;
    }
}
