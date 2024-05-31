<?php

namespace NITSAN\NsFriendlycaptcha\Validation;

use NITSAN\NsFriendlycaptcha\Services\CaptchaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class RecaptchaValidator extends AbstractValidator
{
    protected $acceptsEmptyValues = false;

    /**
     * Checks if the given value is valid according to the validator, and returns
     * the error messages object which occurred.
     *
     * @param mixed $value The value that should be validated
     *
     * @return \TYPO3\CMS\Extbase\Error\Result
     */
    public function validate($value = null)
    {
        if(GeneralUtility::_GP('g-recaptcha-response')){
            $value = trim(GeneralUtility::_GP('g-recaptcha-response'));
        }
        $this->result = new Result();
        if ($this->acceptsEmptyValues === false || $this->isEmpty($value) === false) {
            $this->isValid($value);
        }
        return $this->result;
    }

    /**
     * Validate the captcha value from the request and add an error if not valid
     *
     * @param mixed $value The value
     */
    public function isValid($value)
    {
        /** @var CaptchaService $captcha */
        // @extensionScannerIgnoreLine
        if(version_compare(TYPO3_version, '10.0.0', '<=')){
            $captcha = \NITSAN\NsFriendlycaptcha\Services\CaptchaService::getInstance();
        } else {
            $captcha = GeneralUtility::getContainer()->get(CaptchaService::class);
        }

        if ($captcha !== null) {
            $status = $captcha->validateReCaptcha();

            if (!$status || $status['error'] !== '') {
                $errorText = $this->translateErrorMessage('error_recaptcha_' . $status['error'], 'recaptcha');

                if (empty($errorText)) {
                    $errorText = htmlspecialchars($status['error']);
                }

                $this->addError($errorText, 1519982125);
            }
        }
    }
}
