<?php

namespace NITSAN\NsFriendlycaptcha\Adapter;

use NITSAN\NsFriendlycaptcha\Services\CaptchaService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class TypoScriptAdapter
{
    protected $captchaService;

    public function __construct(CaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
    }

    public function render(): string
    {
        $output = LocalizationUtility::translate(
            'error_captcha.notinstalled',
            'Recaptcha'
        );

        if ($this->captchaService !== null) {
            $output = $this->captchaService->getReCaptcha();

            $status = $this->captchaService->validateReCaptcha();
            if (!$status || $status['error'] !== '') {
                $output .= '<span class="error">' .
                    LocalizationUtility::translate(
                        'error_recaptcha_' . $status['error'],
                        'Recaptcha'
                    ) .
                    '</span>';
            }
        }

        return $output;
    }
}
