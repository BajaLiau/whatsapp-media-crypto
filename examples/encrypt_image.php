<?php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\StreamFactory;

// Paths to the original image and key
$originalImagePath = __DIR__ . '/../samples/original/IMAGE.original';
$keyPath = __DIR__ . '/../samples/original/IMAGE.key';
$outputEncryptedPath = __DIR__ . '/../samples/IMAGE.encrypted';

try {
    // Read the media key and create the source stream
    $mediaKey = file_get_contents($keyPath);
    $source = Utils::streamFor(fopen($originalImagePath, 'rb'));

    // Create the encrypting stream (without sidecar for images)
    $encStream = StreamFactory::createEncryptingStream(
        $source,
        $mediaKey,
        'IMAGE'
    );

    // Write the encrypted data to a new file
    $outputFile = fopen($outputEncryptedPath, 'wb');
    while (!$encStream->eof()) {
        $data = $encStream->read(8192);
        if ($data === '') {
            break;
        }
        fwrite($outputFile, $data);
    }
    fclose($outputFile);

    echo "Encrypted image saved to: $outputEncryptedPath\n";

} catch (\InvalidArgumentException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";
    exit(1);
} catch (\RuntimeException $e) {
    echo "Encryption error: " . $e->getMessage() . "\n";
    exit(1);
}
