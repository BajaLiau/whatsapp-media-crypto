<?php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\StreamFactory;

// Paths to the original audio and key
$originalAudioPath = __DIR__ . '/../samples/original/AUDIO.original';
$keyPath = __DIR__ . '/../samples/original/AUDIO.key';
$outputEncryptedPath = __DIR__ . '/../samples/AUDIO.encrypted';

try {
    // Check if the original files exist
    if (!file_exists($originalAudioPath)) {
        throw new \RuntimeException("Original file not found: $originalAudioPath");
    }
    if (!file_exists($keyPath)) {
        throw new \RuntimeException("Key file not found: $keyPath");
    }

    // Read the media key
    $mediaKey = file_get_contents($keyPath);

    // Open the original audio file
    $source = Utils::streamFor(fopen($originalAudioPath, 'rb'));

    // Create the output directory if it doesn't exist
    $outputDir = dirname($outputEncryptedPath);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    // Create the encrypting stream via factory
    $encStream = StreamFactory::createEncryptingStream(
        $source,
        $mediaKey,
        'AUDIO'
    );

    // Write the encrypted data to a new file
    $outputFile = fopen($outputEncryptedPath, 'wb');
    try {
        while (!$encStream->eof()) {
            $data = $encStream->read(8192);
            if ($data === '') {
                break;
            }
            fwrite($outputFile, $data);
        }
    } finally {
        fclose($outputFile);
    }

    // Check file sizes
    $originalSize = filesize($originalAudioPath);
    $encryptedSize = filesize($outputEncryptedPath);

    echo "Audio successfully encrypted: $outputEncryptedPath\n";
    echo "\nFile information:\n";
    echo "Original file size: " . number_format($originalSize) . " bytes\n";
    echo "Encrypted file size: " . number_format($encryptedSize) . " bytes\n";

} catch (\InvalidArgumentException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";
    exit(1);
} catch (\RuntimeException $e) {
    echo "Encryption error: " . $e->getMessage() . "\n";
    exit(1);
}
