<?php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\MediaKey;
use WhatsAppMedia\Stream\EncryptingStream;

// Paths to the original audio and key
$originalAudioPath = __DIR__ . '/../samples/original/AUDIO.original';
$keyPath = __DIR__ . '/../samples/original/AUDIO.key';
$outputEncryptedPath = __DIR__ . '/../samples/AUDIO.encrypted';

// Read the media key
$mediaKey = file_get_contents($keyPath);
$parts = MediaKey::expand($mediaKey, 'AUDIO');

// Open the original audio file
$source = Utils::streamFor(fopen($originalAudioPath, 'rb'));

// Create the encrypting stream
$encStream = new EncryptingStream($source, $parts['cipherKey'], $parts['macKey'], $parts['iv']);

// Write the encrypted data to a new file
$outputFile = fopen($outputEncryptedPath, 'wb');
while (!$encStream->eof()) {
    fwrite($outputFile, $encStream->read(8192));
}
fclose($outputFile);

// Output success message
echo "Encrypted audio saved to: $outputEncryptedPath\n";
