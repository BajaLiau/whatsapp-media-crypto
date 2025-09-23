<?php
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\StreamFactory;

// File paths
$originalAudioPath = __DIR__ . '/../samples/original/AUDIO.original';
$keyPath = __DIR__ . '/../samples/original/AUDIO.key';
$outputEncryptedPath = __DIR__ . '/../samples/AUDIO.encrypted';
$outputSidecarPath = __DIR__ . '/../samples/AUDIO.sidecar';

try {
    if (!file_exists($originalAudioPath)) {
        throw new \RuntimeException("Source file not found: $originalAudioPath");
    }
    if (!file_exists($keyPath)) {
        throw new \RuntimeException("Key file not found: $keyPath");
    }

    // Read media key
    $mediaKey = file_get_contents($keyPath);

    // Open source stream
    $source = Utils::streamFor(fopen($originalAudioPath, 'rb'));

    // Create encrypting stream with sidecar generation
    $encStream = StreamFactory::createEncryptingStream(
        $source,
        $mediaKey,
        'AUDIO',
        true
    );

    // Ensure output directory exists
    $outputDir = dirname($outputEncryptedPath);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    // Write encrypted data to file
    $outputFile = fopen($outputEncryptedPath, 'wb');
    while (!$encStream->eof()) {
        $data = $encStream->read(8192);
        if ($data === '') {
            break;
        }
        fwrite($outputFile, $data);
    }
    fclose($outputFile);

    // Save sidecar file
    file_put_contents($outputSidecarPath, $encStream->getSidecar());

    echo "Audio successfully encrypted: $outputEncryptedPath\n";
    echo "Sidecar saved: $outputSidecarPath\n";

    // Display file sizes
    echo "\nFile information:\n";
    echo "Original file size: " . number_format(filesize($originalAudioPath)) . " bytes\n";
    echo "Encrypted file size: " . number_format(filesize($outputEncryptedPath)) . " bytes\n";
    echo "Sidecar size: " . number_format(filesize($outputSidecarPath)) . " bytes\n";

} catch (\InvalidArgumentException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";
    exit(1);
} catch (\RuntimeException $e) {
    echo "Encryption error: " . $e->getMessage() . "\n";
    exit(1);
}
