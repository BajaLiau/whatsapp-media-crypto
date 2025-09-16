<?php
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\MediaKey;
use WhatsAppMedia\Stream\EncryptingStream;
use WhatsAppMedia\Stream\DecryptingStream;

class CryptoTest extends TestCase
{
    public function testEncryptDecrypt()
    {
        $original = __DIR__ . '/../samples/original/video.original';
        $mediaKey = file_get_contents(__DIR__ . '/../samples/original/video.key');
        $parts = MediaKey::expand($mediaKey, 'VIDEO');

        $originalData = file_get_contents($original);
        $source = Utils::streamFor(fopen($original, 'rb'));
        $encStream = new EncryptingStream($source, $parts['cipherKey'], $parts['macKey'], $parts['iv']);
        $encrypted = '';
        while (!$encStream->eof()) {
            $encrypted .= $encStream->read(8192);
        }

        $decStream = new DecryptingStream(Utils::streamFor($encrypted), $parts['cipherKey'], $parts['macKey'], $parts['iv']);
        $decrypted = '';
        while (!$decStream->eof()) {
            $decrypted .= $decStream->read(8192);
        }

        $this->assertNotEmpty($encrypted, 'Encrypted data should not be empty.');
        $this->assertTrue(
            hash('sha256', $originalData) === hash('sha256', $decrypted),
            'Decrypted data should match the original data.'
        );
    }
}
