# SRI Sender 🇪🇨

Paquete para hacer recepción y autorización de XML firmados (Factura, Guía de remisión, Nota crédito, Nota débito y Comprobante de retención) a los servidores SRI (Ecuador).

## Instalación

```bash
composer require dazza-dev/sri-sender
```

## Uso

```php
use DazzaDev\SriSender\Sender;

// Instanciar el sender
$sender = new Sender(test: true); // Usar entorno de pruebas (test: true)

// XML como string
$xmlContent = file_get_contents(__DIR__ . '/factura.xml');

// Enviar XML a recepción
$validationResult = $sender->validate($xmlContent);

// Enviar XML a autorización
$accessKey = 'clave_acceso_del_documento';
$authorizationResult = $sender->authorize($accessKey);
```

### Recepción y Autorización en un solo paso

```php

// Enviar XML a recepción y autorización en un solo paso
$result = $sender->send($accessKey, $xmlContent);

if ($result['success']) {
    echo "Validación y autorización exitosas!";
    echo "Status: " . $result['status'];
} else {
    echo "Validación y/o autorización fallidas: " . $result['error'];
}
```

### Uso con Configuración Personalizada

```php
use DazzaDev\SriSender\Sender;
use DazzaDev\SriSender\Config\SriConfig;

// Configuración personalizada
$config = new SriConfig(
    soapOptions: [
        'connection_timeout' => 300,
        'default_socket_timeout' => 300,
        'user_agent' => 'My Custom Agent'
    ],
    retryConfig: [
        'maxAttempts' => 3,
        'delaySeconds' => 2
    ]
);

// Instanciar el sender con la configuración personalizada
$sender = new Sender(test: false, $config);

// Validar XML
try {
    $result = $sender->validate($xmlContent);

    if ($result['success']) {
        echo "Validación exitosa!";
        echo "Status: " . $result['status'];
    } else {
        echo "Validación fallida: " . $result['error'];
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Generar y Firmar XML

Para generar y firmar XML, puedes utilizar los paquetes:

- [SRI XML Generator](https://github.com/dazza-dev/sri-xml-generator)
- [SRI Signer](https://github.com/dazza-dev/sri-signer)

## Contribuciones

Contribuciones son bienvenidas. Si encuentras algún error o tienes ideas para mejoras, por favor abre un issue o envía un pull request. Asegúrate de seguir las guías de contribución.

## Autor

SRI Sender fue creado por [DAZZA](https://github.com/dazza-dev).

## Licencia

Este proyecto está licenciado bajo la [Licencia MIT](https://opensource.org/licenses/MIT).
