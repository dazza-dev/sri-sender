<?php

namespace DazzaDev\SriSender\Clients;

use DazzaDev\SriSender\Config\SriConfig;
use DazzaDev\SriSender\Exceptions\SriAuthorizationException;
use DazzaDev\SriSender\Services\MessageFormatter;
use SoapClient;
use SoapFault;

class AuthorizationClient
{
    private SriConfig $config;

    private MessageFormatter $messageFormatter;

    private ?object $lastResponse = null;

    /**
     * Constructor
     */
    public function __construct(SriConfig $config, MessageFormatter $messageFormatter)
    {
        $this->config = $config;
        $this->messageFormatter = $messageFormatter;
    }

    /**
     * Authorize XML document using access key
     */
    public function authorize(string $accessKey): array
    {
        try {
            $client = $this->createSoapClient();
            $maxAttempts = $this->config->getMaxRetryAttempts();
            $retryDelay = $this->config->getRetryDelay();
            $attempts = 0;

            while ($attempts < $maxAttempts) {
                try {
                    $attempts++;

                    $this->lastResponse = $client->autorizacionComprobante([
                        'claveAccesoComprobante' => $accessKey,
                    ]);

                    if ($this->messageFormatter->isAuthorizationSuccessful($this->lastResponse)) {
                        return [
                            'success' => true,
                            'status' => $this->messageFormatter->getAuthorizationStatus($this->lastResponse),
                            'authorized_document' => [
                                'access_key' => $this->getAuthorizationNumber(),
                                'xml' => $this->getAuthorizedDocument(),
                                'date' => $this->getAuthorizationDate(),
                            ],
                            'messages' => $this->messageFormatter->extractAuthorizationMessages($this->lastResponse),
                            'attempts' => $attempts,
                        ];
                    }

                    // If not successful and we have more attempts, wait and retry
                    if ($attempts < $maxAttempts) {
                        sleep($retryDelay);

                        continue;
                    }

                    // Final attempt failed
                    $messages = $this->messageFormatter->extractAuthorizationMessages($this->lastResponse, 'string');
                    throw new SriAuthorizationException(implode("\n", $messages));
                } catch (SoapFault $e) {
                    if ($attempts >= $maxAttempts) {
                        throw new SriAuthorizationException('Error de conexión con el SRI: '.$e->getMessage());
                    }
                    sleep($retryDelay);
                }
            }

            throw new SriAuthorizationException('No se recibió una respuesta de autorización válida del SRI después de varios intentos.');
        } catch (SriAuthorizationException $e) {
            return [
                'success' => false,
                'status' => $this->getLastStatus(),
                'error' => $e->getMessage(),
                'attempts' => $attempts ?? 0,
            ];
        } catch (SoapFault $e) {
            return [
                'success' => false,
                'status' => 'ERROR',
                'error' => 'Error de conexión con el SRI: '.$e->getMessage(),
                'attempts' => $attempts ?? 0,
            ];
        }
    }

    /**
     * Get messages from last response
     */
    public function getMessages(string $format = 'array'): array
    {
        if ($this->lastResponse === null) {
            return [];
        }

        return $this->messageFormatter->extractAuthorizationMessages($this->lastResponse, $format);
    }

    /**
     * Get status from last response
     */
    public function getLastStatus(): ?string
    {
        if ($this->lastResponse === null) {
            return null;
        }

        return $this->messageFormatter->getAuthorizationStatus($this->lastResponse);
    }

    /**
     * Get last response object
     */
    public function getLastResponse(): ?object
    {
        return $this->lastResponse;
    }

    /**
     * Check if last authorization was successful
     */
    public function wasLastAuthorizationSuccessful(): bool
    {
        if ($this->lastResponse === null) {
            return false;
        }

        return $this->messageFormatter->isAuthorizationSuccessful($this->lastResponse);
    }

    /**
     * Get the authorized document access key from the last SRI response
     */
    public function getAuthorizationNumber(): ?string
    {
        if ($this->lastResponse === null) {
            return null;
        }

        // Navigate through the response structure to get the authorization number
        if (isset($this->lastResponse->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->numeroAutorizacion)) {
            return $this->lastResponse->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->numeroAutorizacion;
        }

        return null;
    }

    /**
     * Get the authorized document (XML) from the last SRI response
     */
    public function getAuthorizedDocument(): ?string
    {
        if ($this->lastResponse === null) {
            return null;
        }

        // Navigate through the response structure to get the comprobante (XML document)
        if (isset($this->lastResponse->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->comprobante)) {
            return $this->lastResponse->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->comprobante;
        }

        return null;
    }

    /**
     * Get the authorization date from the last SRI response
     */
    public function getAuthorizationDate(): ?string
    {
        if ($this->lastResponse === null) {
            return null;
        }

        // Navigate through the response structure to get the fechaAutorizacion
        if (isset($this->lastResponse->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->fechaAutorizacion)) {
            return $this->lastResponse->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->fechaAutorizacion;
        }

        return null;
    }

    /**
     * Create SOAP client for authorization service
     */
    private function createSoapClient(): SoapClient
    {
        return new SoapClient(
            $this->config->getAuthorizationUrl(),
            $this->config->getSoapOptions()
        );
    }
}
