<?php

namespace DazzaDev\SriSender;

use DazzaDev\SriSender\Clients\AuthorizationClient;
use DazzaDev\SriSender\Clients\ReceptionClient;
use DazzaDev\SriSender\Config\SriConfig;
use DazzaDev\SriSender\Exceptions\SriAuthorizationException;
use DazzaDev\SriSender\Exceptions\SriReceptionException;
use DazzaDev\SriSender\Services\MessageFormatter;

class Sender
{
    private SriConfig $config;

    private MessageFormatter $messageFormatter;

    private ReceptionClient $receptionClient;

    private AuthorizationClient $authorizationClient;

    /**
     * Constructor
     */
    public function __construct(
        bool $test = false,
        ?SriConfig $config = null,
        ?MessageFormatter $messageFormatter = null
    ) {
        $this->config = $config ?? new SriConfig;
        $this->config->setTestEnvironment($test);
        $this->messageFormatter = $messageFormatter ?? new MessageFormatter;
        $this->receptionClient = new ReceptionClient($this->config, $this->messageFormatter);
        $this->authorizationClient = new AuthorizationClient($this->config, $this->messageFormatter);
    }

    /**
     * Validate XML with SRI reception service
     */
    public function validate(string $xml): array
    {
        return $this->receptionClient->validate($xml);
    }

    /**
     * Authorize XML with SRI authorization service
     */
    public function authorize(string $accessKey): array
    {
        return $this->authorizationClient->authorize($accessKey);
    }

    /**
     * Get reception messages
     */
    public function getRecepcionMessages(bool $formatted = false): array
    {
        return $this->receptionClient->getMessages($formatted);
    }

    /**
     * Get authorization messages
     */
    public function getAutorizacionMessages(bool $formatted = false): array
    {
        return $this->authorizationClient->getMessages($formatted);
    }

    /**
     * Get reception status
     */
    public function getRecepcionStatus(): ?string
    {
        return $this->receptionClient->getLastStatus();
    }

    /**
     * Get authorization status
     */
    public function getAutorizacionStatus(): ?string
    {
        return $this->authorizationClient->getLastStatus();
    }

    /**
     * Check if last authorization was successful
     */
    public function wasLastAuthorizationSuccessful(): bool
    {
        return $this->authorizationClient->wasLastAuthorizationSuccessful();
    }

    /**
     * Get last reception response
     */
    public function getLastReceptionResponse()
    {
        return $this->receptionClient->getLastResponse();
    }

    /**
     * Get last authorization response
     */
    public function getLastAuthorizationResponse()
    {
        return $this->authorizationClient->getLastResponse();
    }

    /**
     * Send XML to reception and authorization in one step
     */
    public function send(string $accessKey, string $xml): array
    {
        try {
            // Step 1: Validate XML with reception service
            $validationResult = $this->validate($xml);

            if (! $validationResult['success']) {
                throw new SriReceptionException(
                    'Validation failed: '.($validationResult['error'] ?? 'Unknown error')
                );
            }

            // Sleep to avoid executing too fast between validation and authorization
            sleep(3);

            // Step 2: Authorize with the provided access key
            $authorizationResult = $this->authorize($accessKey);

            if (! $authorizationResult['success']) {
                throw new SriAuthorizationException(
                    'Authorization failed: '.($authorizationResult['error'] ?? 'Unknown error')
                );
            }

            // Both validation and authorization successful
            return [
                'success' => true,
                'status' => $this->getAutorizacionStatus(),
                'validation' => $validationResult,
                'authorization' => $authorizationResult,
            ];
        } catch (SriReceptionException $e) {
            return [
                'success' => false,
                'status' => $this->getRecepcionStatus(),
                'error' => $e->getMessage(),
            ];
        } catch (SriAuthorizationException $e) {
            return [
                'success' => false,
                'status' => $this->getAutorizacionStatus(),
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'ERROR',
                'error' => 'Unexpected error: '.$e->getMessage(),
            ];
        }
    }
}
