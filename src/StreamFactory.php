<?php
declare(strict_types=1);

namespace WhatsAppMedia;

use Psr\Http\Message\StreamInterface;
use WhatsAppMedia\Stream\{EncryptingStream, DecryptingStream};

class StreamFactory
{
    private const SUPPORTED_TYPES = [
        'IMAGE',
        'VIDEO',
        'AUDIO',
        'DOCUMENT'
    ];

    private const STREAMING_TYPES = [
        'VIDEO',
        'AUDIO'
    ];

    /**
     * Creates an encrypting stream for the given media type
     *
     * @param StreamInterface $source Source stream to encrypt
     * @param string $mediaKey 32-byte encryption key
     * @param string $mediaType Type of media (IMAGE, VIDEO, AUDIO, DOCUMENT)
     * @param bool $generateSidecar Whether to generate sidecar for streamable media
     * @throws \InvalidArgumentException If media type is invalid or key length is wrong
     * @return StreamInterface
     */
    public static function createEncryptingStream(
        StreamInterface $source,
        string $mediaKey,
        string $mediaType,
        bool $generateSidecar = false
    ): StreamInterface {
        self::validateMediaType($mediaType);
        self::validateMediaKey($mediaKey);

        if ($generateSidecar && !in_array($mediaType, self::STREAMING_TYPES, true)) {
            throw new \InvalidArgumentException('Sidecar generation is only supported for VIDEO and AUDIO types');
        }

        $parts = MediaKey::expand($mediaKey, $mediaType);
        return new EncryptingStream(
            $source,
            $parts['cipherKey'],
            $parts['macKey'],
            $parts['iv'],
            $generateSidecar
        );
    }

    /**
     * Creates a decrypting stream for the given media type
     *
     * @param StreamInterface $source Source stream to decrypt
     * @param string $mediaKey 32-byte encryption key
     * @param string $mediaType Type of media (IMAGE, VIDEO, AUDIO, DOCUMENT)
     * @throws \InvalidArgumentException If media type is invalid or key length is wrong
     * @return StreamInterface
     */
    public static function createDecryptingStream(
        StreamInterface $source,
        string $mediaKey,
        string $mediaType
    ): StreamInterface {
        self::validateMediaType($mediaType);
        self::validateMediaKey($mediaKey);

        $parts = MediaKey::expand($mediaKey, $mediaType);
        return new DecryptingStream(
            $source,
            $parts['cipherKey'],
            $parts['macKey'],
            $parts['iv']
        );
    }

    /**
     * Validates the media type.
     *
     * @param string $mediaType
     * @throws \InvalidArgumentException If the media type is invalid.
     */
    private static function validateMediaType(string $mediaType): void
    {
        if (!in_array($mediaType, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported media type: ' . $mediaType);
        }
    }

    /**
     * Validates the media key length.
     *
     * @param string $mediaKey
     * @throws \InvalidArgumentException If the media key length is invalid.
     */
    private static function validateMediaKey(string $mediaKey): void
    {
        if (strlen($mediaKey) !== 32) {
            throw new \InvalidArgumentException('Media key must be exactly 32 bytes');
        }
    }
}
