<?php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\MediaKey;
use WhatsAppMedia\Stream\EncryptingStream;

// Paths to the original video and key
$originalVideoPath = __DIR__ . '/../samples/original/VIDEO.original';
$keyPath = __DIR__ . '/../samples/original/VIDEO.key';
$outputEncryptedPath = __DIR__ . '/../samples/VIDEO.encrypted';

// Read the media key
$mediaKey = file_get_contents($keyPath);
$parts = MediaKey::expand($mediaKey, 'VIDEO');

// Open the original video file
$source = Utils::streamFor(fopen($originalVideoPath, 'rb'));

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
file_put_contents($logFile, "Sidecar Data: " . bin2hex($encStream->getSidecar()) . "\n", FILE_APPEND);

// Output success message
echo "Encrypted video saved to: $outputEncryptedPath\n";
