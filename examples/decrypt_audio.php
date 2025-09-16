<?php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\MediaKey;
use WhatsAppMedia\Stream\DecryptingStream;

// Paths to the encrypted audio and key
$encryptedAudioPath = __DIR__ . '/../samples/AUDIO.encrypted';
$keyPath = __DIR__ . '/../samples/original/AUDIO.key';
$outputDecryptedPath = __DIR__ . '/../samples/AUDIO.generated.mine';

// Read the media key
$mediaKey = file_get_contents($keyPath);
$parts = MediaKey::expand($mediaKey, 'AUDIO');

// Open the encrypted audio file
$source = Utils::streamFor(fopen($encryptedAudioPath, 'rb'));

// Create the decrypting stream
$decStream = new DecryptingStream($source, $parts['cipherKey'], $parts['macKey'], $parts['iv']);

// Write the decrypted data to a new file
$outputFile = fopen($outputDecryptedPath, 'wb');
while (!$decStream->eof()) {
    fwrite($outputFile, $decStream->read(8192));
}
fclose($outputFile);

// Output success message
echo "Decrypted audio saved to: $outputDecryptedPath\n";

