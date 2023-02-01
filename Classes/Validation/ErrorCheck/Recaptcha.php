<?php

namespace NITSAN\NsFriendlycaptcha\Validation\ErrorCheck;

use NITSAN\NsFriendlycaptcha\Services\CaptchaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Validator\ErrorCheck\AbstractErrorCheck;

/**
 * EXT:formhandler ErrorCheck for Recaptcha.
 *
 * @deprecated with 11.x
 */
class Recaptcha extends AbstractErrorCheck
{
    /**
     * Checks the ReCaptcha.
     *
     * @return string
     */
    public function check(): string
    {
        /** @var CaptchaService $captcha */
        $captcha = GeneralUtility::getContainer()->get(CaptchaService::class);

        $checkFailed = '';
        if ($captcha !== null) {
            $status = $captcha->validateReCaptcha();
            if ($status == false || $status['error'] !== '') {
                $checkFailed = $this->getCheckFailed();
            }
        }

        return $checkFailed;
    }
}
