<?php

declare(strict_types=1);

namespace NITSAN\NsFriendlycaptcha\Adapter;

use NITSAN\NsFriendlycaptcha\Services\CaptchaService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

#[Autoconfigure(public: true)]
class TypoScriptAdapter
{
    public function __construct(protected CaptchaService $captchaService)
    {
    }

    public function render(): string
    {
        $output = $this->captchaService->getReCaptcha();

        $status = $this->captchaService->validateReCaptcha();
        if ($status['error'] !== '') {
            $output .= '<span class="error">'
                . LocalizationUtility::translate('error_recaptcha_' . $status['error'], 'ns_friendlycaptcha')
                . '</span>';
        }

        return $output;
    }
}
