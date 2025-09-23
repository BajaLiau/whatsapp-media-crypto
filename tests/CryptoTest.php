<?php
declare(strict_types=1);

namespace WhatsAppMedia\Tests;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\StreamFactory; // Import StreamFactory

class CryptoTest extends TestCase
{
    private const SAMPLE_DIR = __DIR__ . '/../samples/original/';

    /**
     * Test basic encryption/decryption for all media types
     *
     * @dataProvider mediaTypeProvider
     */
    public function testEncryptDecrypt(string $mediaType)
    {
        $original = self::SAMPLE_DIR . "$mediaType.original";
        $mediaKey = file_get_contents(self::SAMPLE_DIR . "$mediaType.key");
        $originalData = file_get_contents($original);

        // Encryption
        $source = Utils::streamFor(fopen($original, 'rb'));
        $encStream = StreamFactory::createEncryptingStream($source, $mediaKey, $mediaType);
        $encrypted = '';
        while (!$encStream->eof()) {
            $encrypted .= $encStream->read(8192);
        }

        // Decryption
        $decStream = StreamFactory::createDecryptingStream(
            Utils::streamFor($encrypted),
            $mediaKey,
            $mediaType
        );
        $decrypted = '';
        while (!$decStream->eof()) {
            $decrypted .= $decStream->read(8192);
        }

        $this->assertNotEmpty($encrypted, "Encrypted data should not be empty for $mediaType");
        $this->assertEquals(
            hash('sha256', $originalData),
            hash('sha256', $decrypted),
            "Decrypted data should match original for $mediaType"
        );
    }

    /**
     * Test sidecar generation and validation for streamable media
     *
     * @dataProvider streamableMediaTypeProvider
     */
    public function testSidecarGeneration(string $mediaType)
    {
        $original = self::SAMPLE_DIR . "$mediaType.original";
        $mediaKey = file_get_contents(self::SAMPLE_DIR . "$mediaType.key");
        $expectedSidecar = file_get_contents(self::SAMPLE_DIR . "$mediaType.sidecar");

        $source = Utils::streamFor(fopen($original, 'rb'));
        $encStream = StreamFactory::createEncryptingStream($source, $mediaKey, $mediaType, true);

        while (!$encStream->eof()) {
            $encStream->read(8192);
        }

        $generatedSidecar = $encStream->getSidecar();

        $this->assertNotEmpty($generatedSidecar, "Sidecar should not be empty for $mediaType");
        $this->assertEquals(
            $expectedSidecar,
            $generatedSidecar,
            "Generated sidecar should match expected for $mediaType"
        );
    }

    public function testInvalidMac()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MAC verification failed');

        $mediaKey = file_get_contents(self::SAMPLE_DIR . "VIDEO.key");
        $encrypted = "corrupted data";

        $decStream = StreamFactory::createDecryptingStream(
            Utils::streamFor($encrypted),
            $mediaKey,
            'VIDEO'
        );

        while (!$decStream->eof()) {
            $decStream->read(8192);
        }
    }

    public function testSidecarForUnsupportedType()
    {
        $this->expectException(\InvalidArgumentException::class);

        $source = Utils::streamFor('test data');
        $mediaKey = random_bytes(32);

        StreamFactory::createEncryptingStream(
            $source,
            $mediaKey,
            'IMAGE',
            true
        );
    }

    public function testEmptyStream()
    {
        $mediaKey = random_bytes(32);
        $source = Utils::streamFor('');

        $encStream = StreamFactory::createEncryptingStream($source, $mediaKey, 'VIDEO');
        $encrypted = '';
        while (!$encStream->eof()) {
            $encrypted .= $encStream->read(8192);
        }

        $this->assertNotEmpty($encrypted, 'Even empty stream should produce encrypted data (due to padding)');
    }

    public function testInvalidKeyLength()
    {
        $this->expectException(\InvalidArgumentException::class);

        $source = Utils::streamFor('test');
        $invalidKey = random_bytes(16);

        StreamFactory::createEncryptingStream($source, $invalidKey, 'VIDEO');
    }

    public function testConsistentIvForSidecar()
    {
        $mediaType = 'VIDEO';
        $original = self::SAMPLE_DIR . "$mediaType.original";
        $mediaKey = file_get_contents(self::SAMPLE_DIR . "$mediaType.key");

        $source = Utils::streamFor(fopen($original, 'rb'));
        $encStream = StreamFactory::createEncryptingStream($source, $mediaKey, $mediaType, true);

        // Read all data to generate sidecar
        while (!$encStream->eof()) {
            $encStream->read(8192);
        }

        $sidecar = $encStream->getSidecar();

        $this->assertNotEmpty($sidecar, 'Sidecar should be generated');
        $this->assertEquals(strlen($sidecar) % 10, 0, 'Sidecar should contain multiples of 10-byte HMACs');
    }


    public function mediaTypeProvider(): array
    {
        return [
            ['VIDEO'],
            ['AUDIO'],
            ['IMAGE'],
            ['DOCUMENT']
        ];
    }

    public function streamableMediaTypeProvider(): array
    {
        return [
            ['VIDEO'],
            ['AUDIO']
        ];
    }
}
