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
            'ns_friendlycaptcha'
        );

        if (!empty($this->captchaService)) {
            $output = $this->captchaService->getFriendlyCaptcha();
            $status = $this->captchaService->validateFriendlyCaptcha();

            if (!$status || $status['error'] !== '') {
                $errorText = LocalizationUtility::translate(
                    'error_friendlycaptcha_' . $status['error'],
                    'ns_friendlycaptcha'
                );
                // BC: fall back to legacy language keys
                if (empty($errorText)) {
                    $errorText = LocalizationUtility::translate(
                        'error_recaptcha_' . $status['error'],
                        'ns_friendlycaptcha'
                    );
                }
                $output .= '<span class="error">' . $errorText . '</span>';
            }
        }

        return $output;
    }
}
