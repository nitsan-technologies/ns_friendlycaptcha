<?php

declare(strict_types=1);

namespace NITSAN\NsFriendlycaptcha\Services;

use NITSAN\NsFriendlycaptcha\Exception\MissingException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class CaptchaService
{
    public function __construct(
        #[Autowire(expression: 'service("extension-configuration").get("ns_friendlycaptcha")')]
        private array $extensionConfiguration,
        protected ConfigurationManagerInterface $configurationManager,
        protected TypoScriptService $typoScriptService,
        protected ContentObjectRenderer $contentRenderer,
        protected RequestFactory $requestFactory
    ) {
        $this->initialize();
    }

    /**
     * @throws MissingException
     */
    protected function initialize(): void
    {
        if (!is_array($this->extensionConfiguration)) {
            $this->extensionConfiguration = [];
        }

        $typoScriptConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'ns_friendlycaptcha'
        );

        if (!empty($typoScriptConfiguration)) {
            ArrayUtility::mergeRecursiveWithOverrule(
                $this->extensionConfiguration,
                $this->typoScriptService->convertPlainArrayToTypoScriptArray($typoScriptConfiguration),
                true,
                false
            );
        }

        if (!is_array($this->extensionConfiguration) || empty($this->extensionConfiguration)) {
            throw new MissingException(
                'Please configure plugin.tx_ns_friendlycaptcha. before rendering the captcha',
                1417680291
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return $this->extensionConfiguration;
    }

    /**
     * Maps extension setting "autocheck" to Friendly Captcha data-start values: auto, focus, none.
     */
    public function getStartMode(): string
    {
        $autocheck = $this->extensionConfiguration['autocheck'] ?? 'Auto check';

        if (is_int($autocheck) || (is_string($autocheck) && ctype_digit($autocheck))) {
            return match ((int)$autocheck) {
                1 => 'focus',
                2 => 'none',
                default => 'auto',
            };
        }

        $normalized = strtolower(trim((string)$autocheck));

        return match ($normalized) {
            'check on focus', 'focus', 'check_on_focus', 'checkonfocus' => 'focus',
            'manual', 'none' => 'none',
            'auto check', 'auto', 'auto_check', 'autocheck' => 'auto',
            default => 'auto',
        };
    }

    public function getPuzzleEndpoint(): string
    {
        if ($this->isEuEndpointEnabled()) {
            return 'https://eu-api.friendlycaptcha.eu/api/v1/puzzle';
        }

        return 'https://api.friendlycaptcha.com/api/v1/puzzle';
    }

    protected function isEuEndpointEnabled(): bool
    {
        $eu = $this->extensionConfiguration['eu'] ?? false;

        return $eu === true || $eu === 1 || $eu === '1';
    }

    protected function isInRobotMode(): bool
    {
        return (bool)($this->extensionConfiguration['robotMode'] ?? false);
    }

    protected function isDevelopmentMode(): bool
    {
        return Environment::getContext()->isDevelopment();
    }

    protected function isEnforceCaptcha(): bool
    {
        return (bool)($this->extensionConfiguration['enforceCaptcha'] ?? false);
    }

    public function getShowCaptcha(): bool
    {
        return !$this->isInRobotMode()
            && (
                ApplicationType::fromRequest($this->getRequest())->isBackend()
                || !$this->isDevelopmentMode()
                || $this->isEnforceCaptcha()
            );
    }

    public function getReCaptcha(): string
    {
        if ($this->getShowCaptcha()) {
            $captcha = $this->contentRenderer->stdWrap(
                $this->extensionConfiguration['public_key'] ?? '',
                $this->extensionConfiguration['public_key.'] ?? []
            );
        } else {
            $captcha = '<div class="recaptcha-development-mode">
                Development mode active. Do not expect the captcha to appear
            </div>';
        }

        return $captcha ?? '';
    }

    /**
     * @return array{verified: bool, error: string}
     */
    public function validateReCaptcha(string $value = ''): array
    {
        if (!$this->getShowCaptcha()) {
            return [
                'verified' => true,
                'error' => '',
            ];
        }

        $captchaSolution = trim(
            $value !== '' ? $value : (string)($this->getRequest()->getParsedBody()['frc-captcha-solution'] ?? '')
        );

        if ($captchaSolution === '.UNSTARTED' || $captchaSolution === '.UNFINISHED' || $captchaSolution === '.FETCHING') {
            $captchaSolution = '';
        }

        $request = [
            'site_key' => $this->extensionConfiguration['public_key'] ?? '',
            'secret_key' => $this->extensionConfiguration['secret_key'] ?? '',
            'response' => $captchaSolution,
            'remoteip' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            'eu' => $this->extensionConfiguration['eu'] ?? '',
            'enablepuzzle' => $this->extensionConfiguration['enablepuzzle'] ?? '',
        ];

        $result = [
            'verified' => false,
            'error' => '',
        ];

        if ($request['response'] === '') {
            $result['error'] = 'missing-input-response';
        }

        $response = $this->queryVerificationServer($request);
        if (!empty($response['success'])) {
            $result['verified'] = true;
        } elseif (isset($response['error-codes'])) {
            $errorCodes = $response['error-codes'];
            $result['error'] = (string)(is_array($errorCodes) ? reset($errorCodes) : $errorCodes);
        } elseif (isset($response['errors'])) {
            $result['error'] = 'missing-input-response';
        } elseif ($response === []) {
            $result['error'] = 'validation-server-not-responding';
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function queryVerificationServer(array $data): array
    {
        $verifyServer = 'https://api.friendlycaptcha.com/api/v1/siteverify';
        if (!empty($data['eu'])) {
            $verifyServer = 'https://eu-api.friendlycaptcha.eu/api/v1/siteverify';
        }

        if (empty($data['secret_key'])) {
            return [
                'success' => false,
                'error-codes' => 'invalid-input-secret',
            ];
        }

        $body = json_encode([
            'solution' => $data['response'],
            'secret' => $data['secret_key'],
            'sitekey' => $data['site_key'],
        ]);

        try {
            $response = $this->requestFactory->request(
                $verifyServer,
                'POST',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'body' => $body,
                ]
            );
        } catch (\Throwable) {
            return [
                'success' => false,
                'error-codes' => 'validation-server-not-responding',
            ];
        }

        $responseBody = (string)$response->getBody();

        return $responseBody !== '' ? (json_decode($responseBody, true) ?? []) : [];
    }

    protected function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
