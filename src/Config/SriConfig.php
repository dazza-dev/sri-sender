<?php

namespace DazzaDev\SriSender\Config;

class SriConfig
{
    /**
     * Production reception WSDL URL
     */
    private const PRODUCTION_RECEPTION_URL = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';

    /**
     * Test reception WSDL URL
     */
    private const TEST_RECEPTION_URL = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';

    /**
     * Production authorization WSDL URL
     */
    private const PRODUCTION_AUTHORIZATION_URL = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';

    /**
     * Test authorization WSDL URL
     */
    private const TEST_AUTHORIZATION_URL = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';

    /**
     * Default SOAP client options
     */
    private const DEFAULT_SOAP_OPTIONS = [
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'user_agent' => 'SOAP Client',
        'connection_timeout' => 180,
        'default_socket_timeout' => 180,
    ];

    /**
     * Default retry configuration
     */
    private const DEFAULT_RETRY_CONFIG = [
        'max_attempts' => 5,
        'delay_seconds' => 1,
    ];

    private bool $isTestEnvironment;

    private array $soapOptions;

    private array $retryConfig;

    /**
     * Constructor
     */
    public function __construct(
        array $soapOptions = [],
        array $retryConfig = []
    ) {
        $this->soapOptions = array_merge(self::DEFAULT_SOAP_OPTIONS, $soapOptions);
        $this->retryConfig = array_merge(self::DEFAULT_RETRY_CONFIG, $retryConfig);
    }

    /**
     * Set test environment flag
     */
    public function setTestEnvironment(bool $isTestEnvironment): void
    {
        $this->isTestEnvironment = $isTestEnvironment;
    }

    /**
     * Get reception WSDL URL based on environment
     */
    public function getReceptionUrl(): string
    {
        return $this->isTestEnvironment
            ? self::TEST_RECEPTION_URL
            : self::PRODUCTION_RECEPTION_URL;
    }

    /**
     * Get authorization WSDL URL based on environment
     */
    public function getAuthorizationUrl(): string
    {
        return $this->isTestEnvironment
            ? self::TEST_AUTHORIZATION_URL
            : self::PRODUCTION_AUTHORIZATION_URL;
    }

    /**
     * Get SOAP client options
     */
    public function getSoapOptions(): array
    {
        return $this->soapOptions;
    }

    /**
     * Get retry configuration
     */
    public function getRetryConfig(): array
    {
        return $this->retryConfig;
    }

    /**
     * Check if using test environment
     */
    public function isTestEnvironment(): bool
    {
        return $this->isTestEnvironment;
    }

    /**
     * Get maximum retry attempts
     */
    public function getMaxRetryAttempts(): int
    {
        return $this->retryConfig['max_attempts'];
    }

    /**
     * Get retry delay in seconds
     */
    public function getRetryDelay(): int
    {
        return $this->retryConfig['delay_seconds'];
    }
}
