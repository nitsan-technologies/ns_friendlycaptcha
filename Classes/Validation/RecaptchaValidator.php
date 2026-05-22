<?php

declare(strict_types=1);

namespace NITSAN\NsFriendlycaptcha\Validation;

use NITSAN\NsFriendlycaptcha\Services\CaptchaService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class RecaptchaValidator extends AbstractValidator
{
    protected $acceptsEmptyValues = false;

    public function __construct(protected CaptchaService $captchaService)
    {
    }

    public function isValid(mixed $value): void
    {
        $status = $this->captchaService->validateReCaptcha((string)$value);
        if ($status['error'] !== '') {
            $errorText = LocalizationUtility::translate(
                'error_recaptcha_' . $status['error'],
                'ns_friendlycaptcha'
            );

            if (empty($errorText)) {
                $errorText = htmlspecialchars((string)$status['error']);
            }

            $this->addError($errorText, 1519982125);
        }
    }
}
