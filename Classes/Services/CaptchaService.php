<?php
declare(strict_types=1);

namespace NITSAN\NsFriendlycaptcha\Services;

use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

use GuzzleHttp;

class CaptchaService
{
    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * Guzzle Http Client
     * @var GuzzleHttp\Client
     */
    protected $client = null;

    public function __construct(
        ConfigurationManager $configurationManager,
        ExtensionConfiguration $extensionConfiguration,
        TypoScriptService $typoScriptService,
        ContentObjectRenderer $contentObjectRenderer
    )
    {
        $this->initialize();
    }

    public static function getInstance(): CaptchaService
    {
        $instance = GeneralUtility::makeInstance(self::class);
        return $instance;
    }

    /**
     * @throws \NITSAN\NsFriendlycaptcha\Exception\MissingException
     */
    protected function initialize()
    {
        $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->get('ns_friendlycaptcha');

        if (!is_array($configuration)) {
            $configuration = [];
        }

        /** @var ConfigurationManagerInterface $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $typoScriptConfiguration = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'friendlycaptcha'
        );

        if (!empty($typoScriptConfiguration) && is_array($typoScriptConfiguration)) {
            /** @var TypoScriptService $typoScriptService */
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
                $configuration,
                $typoScriptService->convertPlainArrayToTypoScriptArray($typoScriptConfiguration),
                true,
                false
            );
        }

        if (!is_array($configuration) || empty($configuration)) {
            throw new \NITSAN\NsFriendlycaptcha\Exception\MissingException(
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
        $contentRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        return $contentRenderer;
    }

    /**
     * Get development mode for captcha rendering even if TYPO3_CONTENT is not development
     * Based on this the captcha does not get rendered or validated
     */
    protected function isInRobotMode(): bool
    {
        $this->configuration['robotMode'] = isset($this->configuration['robotMode']) ? $this->configuration['robotMode'] : FALSE;
        return (bool) $this->configuration['robotMode'];
    }

    /**
     * Get development mode by TYPO3_CONTEXT
     * Based on this the captcha does not get rendered or validated
     */
    protected function isDevelopmentMode(): bool
    {
        return (bool) FALSE;
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
            && (\TYPO3\CMS\Core\Http\ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend() || !$this->isDevelopmentMode() || $this->isEnforceCaptcha());
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
     */
    public function validateReCaptcha(): array
    {
        if (!$this->getShowCaptcha()) {
            return [
                'verified' => true,
                'error' => ''
            ];
        }

        $captchaSolution = trim(GeneralUtility::_GP('frc-captcha-solution') ?? '');

        $request = [
            'site_key' => $this->configuration['public_key'] ?? '',
            'secreat_key'=>  $this->configuration['secret_key'] ?? '',
            'response' => $captchaSolution,
            'remoteip' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            'eu' => $this->configuration['eu'] ?? '',
            'enablepuzzle' => $this->configuration['enablepuzzle'] ?? ''
        ];
        if($captchaSolution == '.UNSTARTED' || $captchaSolution == '.UNFINISHED' || $captchaSolution == '.FETCHING'){
            $request['response'] = '';    
        }
        $result = ['verified' => false, 'error' => ''];
        if (empty($request['response'])) {
            $result['error'] = 'missing-input-response';
        }

        // Server Side Velidation
        $response = $this->queryVerificationServer($request);
        if($response['success']){
            $result['verified'] = true;
        }else {
            if(isset($response['error-codes'])){
                $result['error'] = $response['error-codes'];
            }
            if(isset($response['errors'])){
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
        if($data['eu']){
            $verifyServerInfo = 'https://eu-api.friendlycaptcha.eu/api/v1/siteverify';
        }

        if(empty($data['secreat_key'])){
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

        $body =json_encode($params);
        $options = [
            'http_errors' => true,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            'body' => $body,
        ];
        try{
            $response = $this->getClient()->post($verifyServerInfo, $options)->getBody()->getContents();
            return json_decode($response, true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if(strpos($e->getMessage(), "secret_invalid")){
                return [
                    'success' => false,
                    'error-codes' => 'Invalid Secret Key',
                    'message' => $e
                ];
            }else {
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