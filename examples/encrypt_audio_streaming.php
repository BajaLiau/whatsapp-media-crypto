<?php
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\StreamFactory;

// Paths to the files
$originalAudioPath = __DIR__ . '/../samples/original/AUDIO.original';
$keyPath = __DIR__ . '/../samples/original/AUDIO.key';
$outputEncryptedPath = __DIR__ . '/../samples/AUDIO.encrypted';
$outputSidecarPath = __DIR__ . '/../samples/AUDIO.sidecar';

try {
    // Read the key and create the source stream
    $mediaKey = file_get_contents($keyPath);
    $source = Utils::streamFor(fopen($originalAudioPath, 'rb'));

    // Create the encrypting stream with sidecar generation
    $encStream = StreamFactory::createEncryptingStream(
        $source,
        $mediaKey,
        'AUDIO',
        true // enable sidecar generation
    );

    // Write the encrypted data
    $outputFile = fopen($outputEncryptedPath, 'wb');
    while (!$encStream->eof()) {
        $data = $encStream->read(8192);
        if ($data === '') {
            break;
        }
        fwrite($outputFile, $data);
    }
    fclose($outputFile);

    // Save the sidecar
    file_put_contents($outputSidecarPath, $encStream->getSidecar());

    echo "Audio successfully encrypted: $outputEncryptedPath\n";
    echo "Sidecar saved: $outputSidecarPath\n";

} catch (\InvalidArgumentException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";
    exit(1);
} catch (\RuntimeException $e) {
    echo "Encryption error: " . $e->getMessage() . "\n";
    exit(1);
}
