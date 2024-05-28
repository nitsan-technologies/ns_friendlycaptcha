<?php
declare(strict_types=1);

namespace NITSAN\NsFriendlycaptcha\Validation;

use NITSAN\NsFriendlycaptcha\Services\CaptchaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

class RecaptchaValidator extends AbstractValidator
{
    protected $acceptsEmptyValues = false;

    /**
     * Checks if the given value is valid according to the validator, and returns
     * the error messages object which occurred.
     *
     * @param mixed $value The value that should be validated
     *
     * @return Result
     * @throws ContentRenderingException
     */
    public function validate(mixed $value = null): Result
    {
        $content = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentData = $content->getRequest()->getParsedBody();
        $value = '';
        if(!empty($contentData['g-recaptcha-response'])){
            $value = trim($contentData['g-recaptcha-response']);
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
     * @throws ContentRenderingException
     */
    public function isValid(mixed $value): void
    {
        /** @var CaptchaService $captcha */
        $captcha = GeneralUtility::getContainer()->get(CaptchaService::class);

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
