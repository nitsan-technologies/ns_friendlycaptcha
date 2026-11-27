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

            // Fail closed on missing/failed verification (do not rely on error alone)
            if (empty($status['verified'])) {
                $errorCode = (string)($status['error'] ?? '');
                if ($errorCode === '') {
                    $errorCode = 'invalid-input-response';
                }

                $errorText = LocalizationUtility::translate(
                    'error_friendlycaptcha_' . $errorCode,
                    'ns_friendlycaptcha'
                );
                // BC: fall back to legacy language keys
                if (empty($errorText)) {
                    $errorText = LocalizationUtility::translate(
                        'error_recaptcha_' . $errorCode,
                        'ns_friendlycaptcha'
                    );
                }
                if (empty($errorText)) {
                    $errorText = $errorCode;
                }
                $output .= '<span class="error">' . htmlspecialchars((string)$errorText) . '</span>';
            }
        }

        return $output;
    }
}
