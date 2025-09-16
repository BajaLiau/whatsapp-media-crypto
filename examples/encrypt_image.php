<?php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\MediaKey;
use WhatsAppMedia\Stream\EncryptingStream;

// Paths to the original image and key
$originalImagePath = __DIR__ . '/../samples/original/IMAGE.original';
$keyPath = __DIR__ . '/../samples/original/IMAGE.key';
$outputEncryptedPath = __DIR__ . '/../samples/IMAGE.encrypted';

// Read the media key
$mediaKey = file_get_contents($keyPath);
$parts = MediaKey::expand($mediaKey, 'IMAGE');

// Open the original image file
$source = Utils::streamFor(fopen($originalImagePath, 'rb'));

// Create the encrypting stream
$encStream = new EncryptingStream($source, $parts['cipherKey'], $parts['macKey'], $parts['iv']);

// Write the encrypted data to a new file
$outputFile = fopen($outputEncryptedPath, 'wb');
while (!$encStream->eof()) {
    fwrite($outputFile, $encStream->read(8192));
}
fclose($outputFile);

// Log final HMAC and sidecar data for debugging
file_put_contents($logFile, "Final HMAC: Placeholder for debugging\n", FILE_APPEND);

// Output success message
echo "Encrypted image saved to: $outputEncryptedPath\n";
