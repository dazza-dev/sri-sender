<?php

namespace DazzaDev\SriSender\Services;

class MessageFormatter
{
    /**
     * Format message object to array or string
     */
    public function formatMessage(object $message, string $format = 'array'): array|string
    {
        $type = $message->tipo ?? 'ERROR';
        $code = $message->identificador ?? '0';
        $messageText = $message->mensaje ?? 'Error en recepción';
        $additionalInfo = $message->informacionAdicional ?? '';

        if ($format === 'array') {
            return [
                'type' => $type,
                'code' => $code,
                'message' => $messageText,
                'additionalInfo' => $additionalInfo,
            ];
        }

        return $type.' '.$code.': '.$messageText.' '.$additionalInfo;
    }

    /**
     * Extract messages from reception response
     */
    public function extractReceptionMessages(object $response, string $format = 'array'): array
    {
        $messages = [];

        if (
            isset($response->RespuestaRecepcionComprobante->comprobantes) &&
            isset($response->RespuestaRecepcionComprobante->comprobantes->comprobante) &&
            isset($response->RespuestaRecepcionComprobante->comprobantes->comprobante->mensajes)
        ) {
            $comprobante = $response->RespuestaRecepcionComprobante->comprobantes->comprobante;
            foreach ($comprobante->mensajes as $message) {
                $messages[] = $this->formatMessage($message, $format);
            }
        }

        return $messages;
    }

    /**
     * Extract messages from authorization response
     */
    public function extractAuthorizationMessages(object $response, string $format = 'array'): array
    {
        $messages = [];

        if (isset($response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion)) {
            $autorizacion = $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion;

            if (isset($autorizacion->mensajes)) {
                foreach ($autorizacion->mensajes as $message) {
                    if (is_array($message)) {
                        foreach ($message as $msg) {
                            $messages[] = $this->formatMessage($msg, $format);
                        }
                    } else {
                        $messages[] = $this->formatMessage($message, $format);
                    }
                }
            }
        }

        return $messages;
    }

    /**
     * Get reception status from response
     */
    public function getReceptionStatus(object $response): ?string
    {
        return $response->RespuestaRecepcionComprobante->estado ?? null;
    }

    /**
     * Get authorization status from response
     */
    public function getAuthorizationStatus(object $response): ?string
    {
        if (! isset($response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion)) {
            return null;
        }

        $autorizacion = $response->RespuestaAutorizacionComprobante
            ->autorizaciones
            ->autorizacion;

        $autorizacion = is_array($autorizacion) ? $autorizacion[0] : $autorizacion;

        return $autorizacion->estado ?? null;
    }

    /**
     * Check if reception was successful
     */
    public function isReceptionSuccessful(object $response): bool
    {
        return $this->getReceptionStatus($response) === 'RECIBIDA';
    }

    /**
     * Check if authorization was successful
     */
    public function isAuthorizationSuccessful(object $response): bool
    {
        return $this->getAuthorizationStatus($response) === 'AUTORIZADO';
    }
}
