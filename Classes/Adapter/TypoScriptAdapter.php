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
        if ($this->captchaService !== null) {
            $output = $this->captchaService->getReCaptcha();

            $status = $this->captchaService->validateReCaptcha();
            if ($status == false || $status['error'] !== '') {
                $output .= '<span class="error">' .
                    LocalizationUtility::translate(
                        'error_recaptcha_' . $status['error'],
                        'Recaptcha'
                    ) .
                    '</span>';
            }
        } else {
            $output = LocalizationUtility::translate(
                'error_captcha.notinstalled',
                'Recaptcha'
            );
        }

        return $output;
    }
}
