<?php
declare(strict_types=1);

namespace WhatsAppMedia;

/**
 * Handles media key expansion for WhatsApp media encryption
 */
class MediaKey
{
    private const MEDIA_TYPES = [
        'IMAGE' => 'WhatsApp Image Keys',
        'VIDEO' => 'WhatsApp Video Keys',
        'AUDIO' => 'WhatsApp Audio Keys',
        'DOCUMENT' => 'WhatsApp Document Keys',
    ];

    /**
     * Expands a 32-byte media key into encryption components using HKDF
     *
     * @param string $mediaKey 32-byte key used for media encryption
     * @param string $mediaType Type of media (IMAGE, VIDEO, AUDIO, DOCUMENT)
     * @throws \InvalidArgumentException If media key length is invalid
     * @return array{
     *     iv: string,
     *     cipherKey: string,
     *     macKey: string,
     *     refKey: string
     * }
     */
    public static function expand(string $mediaKey, string $mediaType): array
    {
        if (strlen($mediaKey) !== 32) {
            throw new \InvalidArgumentException('Media key must be exactly 32 bytes');
        }

        $info = self::MEDIA_TYPES[$mediaType] ?? self::MEDIA_TYPES['DOCUMENT'];
        $expanded = HKDF::derive($mediaKey, $info, 112);

        return [
            'iv' => substr($expanded, 0, 16),
            'cipherKey' => substr($expanded, 16, 32),
            'macKey' => substr($expanded, 48, 32),
            'refKey' => substr($expanded, 80, 32),
        ];
    }
}
