<?php
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\StreamFactory;

// File paths
$originalVideoPath = __DIR__ . '/../samples/original/VIDEO.original';
$keyPath = __DIR__ . '/../samples/original/VIDEO.key';
$outputEncryptedPath = __DIR__ . '/../samples/VIDEO.encrypted';
$outputSidecarPath = __DIR__ . '/../samples/VIDEO.sidecar';

try {
    if (!file_exists($originalVideoPath)) {
        throw new \RuntimeException("Source file not found: $originalVideoPath");
    }
    if (!file_exists($keyPath)) {
        throw new \RuntimeException("Key file not found: $keyPath");
    }

    // Read media key
    $mediaKey = file_get_contents($keyPath);

    // Open source stream
    $source = Utils::streamFor(fopen($originalVideoPath, 'rb'));

    // Create encrypting stream with sidecar generation
    $encStream = StreamFactory::createEncryptingStream(
        $source,
        $mediaKey,
        'VIDEO',
        true
    );

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

    echo "Video successfully encrypted: $outputEncryptedPath\n";
    echo "Sidecar saved: $outputSidecarPath\n";

    echo "\nFile information:\n";
    echo "Original file size: " . number_format(filesize($originalVideoPath)) . " bytes\n";
    echo "Encrypted file size: " . number_format(filesize($outputEncryptedPath)) . " bytes\n";
    echo "Sidecar size: " . number_format(filesize($outputSidecarPath)) . " bytes\n";

} catch (\InvalidArgumentException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";
    exit(1);
} catch (\RuntimeException $e) {
    echo "Encryption error: " . $e->getMessage() . "\n";
    exit(1);
}
