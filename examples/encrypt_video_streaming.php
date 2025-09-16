<?php
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\StreamFactory;

// Пути к файлам
$originalVideoPath = __DIR__ . '/../samples/original/VIDEO.original';
$keyPath = __DIR__ . '/../samples/original/VIDEO.key';
$outputEncryptedPath = __DIR__ . '/../samples/VIDEO.encrypted';
$outputSidecarPath = __DIR__ . '/../samples/VIDEO.sidecar';

// Читаем ключ
$mediaKey = file_get_contents($keyPath);

// Открываем исходный файл
$source = Utils::streamFor(fopen($originalVideoPath, 'rb'));

try {
    // Создаём шифрующий поток с генерацией сайдкара
    $encStream = StreamFactory::createEncryptingStream(
        $source,
        $mediaKey,
        'VIDEO',
        true // включаем генерацию сайдкара
    );

    // Записываем зашифрованные данные
    $outputFile = fopen($outputEncryptedPath, 'wb');
    while (!$encStream->eof()) {
        $data = $encStream->read(8192);
        if ($data === '') {
            break;
        }
        fwrite($outputFile, $data);
    }
    fclose($outputFile);

    // Сохраняем сайдкар
    file_put_contents($outputSidecarPath, $encStream->getSidecar());

    echo "Видео успешно зашифровано: $outputEncryptedPath\n";
    echo "Сайдкар сохранен: $outputSidecarPath\n";

} catch (\InvalidArgumentException $e) {
    echo "Ошибка валидации: " . $e->getMessage() . "\n";
    exit(1);
} catch (\RuntimeException $e) {
    echo "Ошибка шифрования: " . $e->getMessage() . "\n";
    exit(1);
}
