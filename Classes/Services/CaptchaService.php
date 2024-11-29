<?php

declare(strict_types=1);

namespace NITSAN\NsFriendlycaptcha\Services;

use NITSAN\NsFriendlycaptcha\Exception\MissingException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

use GuzzleHttp;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

class CaptchaService
{
    /**
     * @var array
     */
    protected array $configuration = [];

    /**
     * Guzzle Http Client
     * @var GuzzleHttp\Client
     */
    protected GuzzleHttp\Client $client;

    /**
     * @throws MissingException
     */
    public function __construct()
    {
        $this->initialize();
    }

    public static function getInstance(): CaptchaService
    {
        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * @throws MissingException
     */
    protected function initialize(): void
    {
        $configuration = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get('ns_friendlycaptcha');

        /** @var ConfigurationManagerInterface $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $typoScriptConfiguration = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'friendlycaptcha'
        );

        if (!empty($typoScriptConfiguration) && is_array($typoScriptConfiguration)) {
            /** @var TypoScriptService $typoScriptService */
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            ArrayUtility::mergeRecursiveWithOverrule(
                $configuration,
                $typoScriptService->convertPlainArrayToTypoScriptArray($typoScriptConfiguration),
                true,
                false
            );
        }

        if (!is_array($configuration) || empty($configuration)) {
            throw new MissingException(
                'Please configure plugin.tx_recaptcha. before rendering the recaptcha',
                1417680291
            );
        }

        $this->configuration = $configuration;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    protected function getContentObjectRenderer(): ContentObjectRenderer
    {
        /** @var ContentObjectRenderer $contentRenderer */
        return GeneralUtility::makeInstance(ContentObjectRenderer::class);
    }

    /**
     * Get development mode for captcha rendering even if TYPO3_CONTENT is not development
     * Based on this the captcha does not get rendered or validated
     */
    protected function isInRobotMode(): bool
    {
        $this->configuration['robotMode'] = $this->configuration['robotMode'] ?? false;
        return (bool) $this->configuration['robotMode'];
    }

    /**
     * Get development mode by TYPO3_CONTEXT
     * Based on this the captcha does not get rendered or validated
     */
    protected function isDevelopmentMode(): bool
    {
        return (bool) false;
    }

    /**
     * Get enforcing captcha rendering even if development mode is true
     */
    protected function isEnforceCaptcha(): bool
    {
        return (bool) $this->configuration['enforceCaptcha'];
    }

    public function getShowCaptcha(): bool
    {
        return !$this->isInRobotMode()
            && (
                ApplicationType::fromRequest(
                    $GLOBALS['TYPO3_REQUEST']
                )->isBackend() || !$this->isDevelopmentMode() || $this->isEnforceCaptcha()
            );
    }

    /**
     * Build reCAPTCHA Frontend HTML-Code
     *
     * @return string reCAPTCHA HTML-Code
     */
    public function getReCaptcha(): string
    {
        if ($this->getShowCaptcha()) {
            $captcha = $this->getContentObjectRenderer()->stdWrap(
                $this->configuration['public_key'],
                $this->configuration['public_key.']
            );
        } else {
            $captcha = '<div class="recaptcha-development-mode">
                Development mode active. Do not expect the captcha to appear
                </div>';
        }

        return $captcha;
    }

    /**
     * Validate reCAPTCHA challenge/response
     *
     * @return array Array with verified- (boolean) and error-code (string)
     * @throws ContentRenderingException
     */
    public function validateReCaptcha(): array
    {
        if (!$this->getShowCaptcha()) {
            return [
                'verified' => true,
                'error' => ''
            ];
        }
        $content = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentData = $content->getRequest()->getParsedBody();
        $captchaSolution = trim($contentData['frc-captcha-solution'] ?? '');

        $request = [
            'site_key' => $this->configuration['public_key'] ?? '',
            'secreat_key' =>  $this->configuration['secret_key'] ?? '',
            'response' => $captchaSolution,
            'remoteip' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            'eu' => $this->configuration['eu'] ?? '',
            'enablepuzzle' => $this->configuration['enablepuzzle'] ?? ''
        ];
        if($captchaSolution == '.UNSTARTED' || $captchaSolution == '.UNFINISHED' || $captchaSolution == '.FETCHING') {
            $request['response'] = '';
        }
        $result = ['verified' => false, 'error' => ''];
        if (empty($request['response'])) {
            $result['error'] = 'missing-input-response';
        }

        // Server Side Velidation
        $response = $this->queryVerificationServer($request);
        if($response['success']) {
            $result['verified'] = true;
        } else {
            if(isset($response['error-codes'])) {
                $result['error'] = $response['error-codes'];
            }
            if(isset($response['errors'])) {
                $result['error'] = 'missing-input-response';
            }
        }
        return $result;
    }

    /**
     * Query reCAPTCHA server for captcha-verification
     *
     * @param array $data
     *
     * @return array Array with verified- (boolean) and error-code (string)
     */
    protected function queryVerificationServer(array $data): array
    {
        $verifyServerInfo = 'https://api.friendlycaptcha.com/api/v1/siteverify';
        if($data['eu']) {
            $verifyServerInfo = 'https://eu-api.friendlycaptcha.eu/api/v1/siteverify';
        }

        if(empty($data['secreat_key'])) {
            return [
                'success' => false,
                'error-codes' => 'Invalid Secret Key'
            ];
        }

        $params = [
            'solution' => $data['response'],
            'secret' => $data['secreat_key'],
            'sitekey' => $data['site_key'],
        ];

        $body = json_encode($params);
        $options = [
            'http_errors' => true,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            'body' => $body,
        ];
        try {
            $response = $this->getClient()->post($verifyServerInfo, $options)->getBody()->getContents();
            return json_decode($response, true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if(strpos($e->getMessage(), "secret_invalid")) {
                return [
                    'success' => false,
                    'error-codes' => 'Invalid Secret Key',
                    'message' => $e
                ];
            } else {
                return [
                    'success' => false,
                    'error-codes' => 'validation-server-not-responding',
                    'message' => $e
                ];
            }
        }
    }

    /**
     * Gets to guzzle client model
     * @return GuzzleHttp\Client
     */
    public function getClient(): GuzzleHttp\Client
    {
        $this->client = new \GuzzleHttp\Client([]);
        return $this->client;
    }
}
