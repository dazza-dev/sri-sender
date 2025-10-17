<?php

namespace DazzaDev\SriSender\Clients;

use DazzaDev\SriSender\Config\SriConfig;
use DazzaDev\SriSender\Exceptions\SriReceptionException;
use DazzaDev\SriSender\Services\MessageFormatter;
use SoapClient;
use SoapFault;

class ReceptionClient
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
     * Validate signed XML document
     */
    public function validate(string $signedXml): array
    {
        try {
            $client = $this->createSoapClient();

            $this->lastResponse = $client->validarComprobante([
                'xml' => $signedXml,
            ]);

            if (! $this->messageFormatter->isReceptionSuccessful($this->lastResponse)) {
                $messages = $this->messageFormatter->extractReceptionMessages($this->lastResponse, 'string');
                throw new SriReceptionException(implode("\n", $messages));
            }

            return [
                'success' => true,
                'status' => $this->messageFormatter->getReceptionStatus($this->lastResponse),
                'messages' => $this->messageFormatter->extractReceptionMessages($this->lastResponse),
            ];
        } catch (SriReceptionException $e) {
            return [
                'success' => false,
                'status' => $this->getLastStatus(),
                'error' => $e->getMessage(),
            ];
        } catch (SoapFault $e) {
            return [
                'success' => false,
                'status' => 'ERROR',
                'error' => 'Error de conexión con el SRI: '.$e->getMessage(),
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

        return $this->messageFormatter->extractReceptionMessages($this->lastResponse, $format);
    }

    /**
     * Get status from last response
     */
    public function getLastStatus(): ?string
    {
        if ($this->lastResponse === null) {
            return null;
        }

        return $this->messageFormatter->getReceptionStatus($this->lastResponse);
    }

    /**
     * Get last response object
     */
    public function getLastResponse(): ?object
    {
        return $this->lastResponse;
    }

    /**
     * Create SOAP client for reception service
     */
    private function createSoapClient(): SoapClient
    {
        return new SoapClient(
            $this->config->getReceptionUrl(),
            $this->config->getSoapOptions()
        );
    }
}
