<?php
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\StreamFactory;

// Пути к файлам
$encryptedVideoPath = __DIR__ . '/../samples/original/VIDEO.encrypted';
$keyPath = __DIR__ . '/../samples/original/VIDEO.key';
$outputDecryptedPath = __DIR__ . '/../samples/VIDEO.decrypted';

try {
    // Проверяем существование файлов
    if (!file_exists($encryptedVideoPath)) {
        throw new \RuntimeException("Зашифрованный файл не найден: $encryptedVideoPath");
    }
    if (!file_exists($keyPath)) {
        throw new \RuntimeException("Файл ключа не найден: $keyPath");
    }

    // Читаем ключ и создаем поток зашифрованного файла
    $mediaKey = file_get_contents($keyPath);
    $source = Utils::streamFor(fopen($encryptedVideoPath, 'rb'));

    // Создаём дешифрующий поток
    $decStream = StreamFactory::createDecryptingStream(
        $source,
        $mediaKey,
        'VIDEO'
    );

    // Создаем директорию для выходного файла, если её нет
    $outputDir = dirname($outputDecryptedPath);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    // Записываем расшифрованные данные
    $outputFile = fopen($outputDecryptedPath, 'wb');
    try {
        while (!$decStream->eof()) {
            $data = $decStream->read(8192);
            if ($data === '') {
                break;
            }
            fwrite($outputFile, $data);
        }
    } finally {
        fclose($outputFile);
    }

    echo "Видео успешно расшифровано: $outputDecryptedPath\n";

} catch (\InvalidArgumentException $e) {
    echo "Ошибка валидации: " . $e->getMessage() . "\n";
    exit(1);
} catch (\RuntimeException $e) {
    echo "Ошибка расшифровки: " . $e->getMessage() . "\n";
    exit(1);
}
