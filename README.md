# WhatsApp Media Crypto

A PHP library for encrypting and decrypting media files using WhatsApp's encryption algorithms.

## Requirements

- PHP 7.4 or higher
- OpenSSL extension
- Composer

## Installation

```bash
composer require your-vendor/whatsapp-media-crypto
```

## Features

- PSR-7 compatible stream decorators for media encryption/decryption
- Support for various media types (images, videos, audio, documents)
- Streaming support for audio and video files
- Sidecar generation for streamable media

## Usage Examples

### Basic Encryption

```php
use WhatsAppMedia\MediaKey;
use WhatsAppMedia\Stream\EncryptingStream;
use GuzzleHttp\Psr7\Utils;

$mediaKey = random_bytes(32); // or use an existing key
$source = Utils::streamFor(fopen('image.jpg', 'rb'));

$parts = MediaKey::expand($mediaKey, 'IMAGE');
$encryptedStream = new EncryptingStream(
    $source,
    $parts['cipherKey'],
    $parts['macKey'],
    $parts['iv']
);
```

### Basic Decryption

```php
use WhatsAppMedia\MediaKey;
use WhatsAppMedia\Stream\DecryptingStream;
use GuzzleHttp\Psr7\Utils;

$mediaKey = // your media key
$source = Utils::streamFor(fopen('encrypted_image', 'rb'));

$parts = MediaKey::expand($mediaKey, 'IMAGE');
$decryptedStream = new DecryptingStream(
    $source,
    $parts['cipherKey'],
    $parts['macKey'],
    $parts['iv']
);
```

### Streaming Support

```php
use WhatsAppMedia\Stream\EncryptingStreamWithSidecar;

$encryptedStream = new EncryptingStreamWithSidecar(
    $source,
    $parts['cipherKey'],
    $parts['macKey'],
    $parts['iv']
);
```

## Media Types

The library supports different media types with their specific application info:

- IMAGE: "WhatsApp Image Keys"
- VIDEO: "WhatsApp Video Keys"
- AUDIO: "WhatsApp Audio Keys"
- DOCUMENT: "WhatsApp Document Keys"

## Running Tests

```bash
vendor/bin/phpunit
```

## Implementation Details

The encryption/decryption process follows WhatsApp's media encryption protocol:

1. Media key expansion using HKDF with SHA-256
2. AES-CBC encryption/decryption
3. HMAC SHA-256 validation
4. Optional sidecar generation for streamable media

## License

This project is licensed under the MIT License.
