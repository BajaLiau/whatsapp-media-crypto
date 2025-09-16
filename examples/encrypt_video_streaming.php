<?php
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsAppMedia\StreamFactory;

// Пути к файлам
$originalVideoPath = __DIR__ . '/../samples/original/VIDEO.original';
$keyPath = __DIR__ . '/../samples/original/VIDEO.key';
$outputEncryptedPath = __DIR__ . '/../samples/VIDEO.encrypted';
$outputSidecarPath = __DIR__ . '/../samples/VIDEO.sidecar';

try {
    // Проверяем существование исходных файлов
    if (!file_exists($originalVideoPath)) {
        throw new \RuntimeException("Исходный файл не найден: $originalVideoPath");
    }
    if (!file_exists($keyPath)) {
        throw new \RuntimeException("Файл ключа не найден: $keyPath");
    }

    // Читаем ключ
    $mediaKey = file_get_contents($keyPath);

    // Открываем исходный файл
    $source = Utils::streamFor(fopen($originalVideoPath, 'rb'));

    // Создаем директорию для выходных файлов, если её нет
    $outputDir = dirname($outputEncryptedPath);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    // Создаём шифрующий поток с генерацией сайдкара
    $encStream = StreamFactory::createEncryptingStream(
        $source,
        $mediaKey,
        'VIDEO',
        true // включаем генерацию сайдкара
    );

    // Записываем зашифрованные данные
    $outputFile = fopen($outputEncryptedPath, 'wb');
    try {
        while (!$encStream->eof()) {
            $data = $encStream->read(8192);
            if ($data === '') {
                break;
            }
            fwrite($outputFile, $data);
        }
    } finally {
        fclose($outputFile);
    }

    // После полного шифрования сохраняем сайдкар
    file_put_contents($outputSidecarPath, $encStream->getSidecar());

    echo "Видео успешно зашифровано: $outputEncryptedPath\n";
    echo "Сайдкар сохранен: $outputSidecarPath\n";

    // Проверяем размеры файлов
    $originalSize = filesize($originalVideoPath);
    $encryptedSize = filesize($outputEncryptedPath);
    $sidecarSize = filesize($outputSidecarPath);

    echo "\nИнформация о файлах:\n";
    echo "Размер исходного файла: " . number_format($originalSize) . " байт\n";
    echo "Размер зашифрованного файла: " . number_format($encryptedSize) . " байт\n";
    echo "Размер сайдкара: " . number_format($sidecarSize) . " байт\n";

} catch (\InvalidArgumentException $e) {
    echo "Ошибка валидации: " . $e->getMessage() . "\n";
    exit(1);
} catch (\RuntimeException $e) {
    echo "Ошибка шифрования: " . $e->getMessage() . "\n";
    exit(1);
}
