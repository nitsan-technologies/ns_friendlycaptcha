<?php

namespace NITSAN\NsFriendlycaptcha\Adapter;

use NITSAN\NsFriendlycaptcha\Services\CaptchaService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

class TypoScriptAdapter
{
    protected CaptchaService $captchaService;

    public function __construct(CaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
    }

    /**
     * @throws ContentRenderingException
     */
    public function render(): string
    {
        $output = LocalizationUtility::translate(
            'error_captcha.notinstalled',
            'Recaptcha'
        );

        if (!empty($this->captchaService)) {
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
