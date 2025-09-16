<?php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\StreamFactory;

// Paths to the original video and key
$originalVideoPath = __DIR__ . '/../samples/original/VIDEO.original';
$keyPath = __DIR__ . '/../samples/original/VIDEO.key';
$outputEncryptedPath = __DIR__ . '/../samples/VIDEO.encrypted';

// Read the media key
$mediaKey = file_get_contents($keyPath);

// Open the original video file
$source = Utils::streamFor(fopen($originalVideoPath, 'rb'));

// Create the encrypting stream using factory
$encStream = StreamFactory::createEncryptingStream($source, $mediaKey, 'VIDEO');

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

echo "Encrypted video saved to: $outputEncryptedPath\n";
