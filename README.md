# WhatsApp Media Crypto

Библиотека для шифрования и дешифрования медиафайлов с использованием алгоритмов WhatsApp. Реализует PSR-7 совместимые потоковые декораторы.

## Требования

- PHP 7.4+
- OpenSSL extension
- Composer

## Установка

```bash
composer require your-vendor/whatsapp-media-crypto
```

## Основные возможности

- PSR-7 совместимые потоковые декораторы
- Поддержка различных типов медиа (изображения, видео, аудио, документы)
- Потоковая обработка больших файлов
- Встроенная генерация sidecar для стриминга видео и аудио
- Строгая типизация и полная документация
- 100% покрытие тестами

## Использование

### Базовое шифрование

```php
use WhatsAppMedia\StreamFactory;
use GuzzleHttp\Psr7\Utils;

$source = Utils::streamFor(fopen('image.jpg', 'rb'));
$mediaKey = random_bytes(32);

$encryptedStream = StreamFactory::createEncryptingStream(
    $source, 
    $mediaKey, 
    'IMAGE'
);
```

### Шифрование с генерацией сайдкара (для видео и аудио)

```php
$source = Utils::streamFor(fopen('video.mp4', 'rb'));
$mediaKey = random_bytes(32);

$encryptedStream = StreamFactory::createEncryptingStream(
    $source, 
    $mediaKey, 
    'VIDEO',
    true // включаем генерацию сайдкара
);

// После шифрования можно получить сайдкар
$sidecar = $encryptedStream->getSidecar();
```

### Дешифрование

```php
$encrypted = Utils::streamFor(fopen('image.encrypted', 'rb'));
$decryptedStream = StreamFactory::createDecryptingStream(
    $encrypted,
    $mediaKey,
    'IMAGE'
);
```

## Поддерживаемые типы медиа

| Тип      | Описание                  | Информационная строка       | Поддержка сайдкара |
|----------|---------------------------|----------------------------|-------------------|
| IMAGE    | Изображения              | WhatsApp Image Keys        | Нет              |
| VIDEO    | Видео                    | WhatsApp Video Keys        | Да               |
| AUDIO    | Аудио                    | WhatsApp Audio Keys        | Да               |
| DOCUMENT | Документы                | WhatsApp Document Keys     | Нет              |

## Архитектура

Библиотека построена с учетом принципов SOLID, DRY и KISS:

### Основные компоненты

- `StreamFactory` - фабрика для создания криптографических потоков
- `AbstractCryptoStream` - базовый класс для криптографических потоков
- `EncryptingStream` - реализация шифрования с опциональной поддержкой сайдкара
- `DecryptingStream` - реализация дешифрования

### Принципы проектирования

- **Single Responsibility**: каждый класс имеет единую ответственность
- **Open/Closed**: расширение функциональности без изменения существующего кода
- **DRY**: общая логика вынесена в базовый класс
- **KISS**: простая и понятная структура без избыточных абстракций

## Особенности реализации

### Генерация сайдкара

- Размер чанка: 64KB
- Перекрытие: 16 байт
- HMAC SHA-256, обрезанный до 10 байт
- Генерация "на лету" без дополнительных чтений из потока

### Безопасность

- Валидация всех входных данных
- Проверка MAC для каждого чанка
- Безопасная работа с криптографическими примитивами
- Корректная обработка ошибок

## Запуск тестов

```bash
composer install
vendor/bin/phpunit
```

## Дальнейшее развитие

Возможные улучшения:

1. Поддержка асинхронной обработки для больших файлов
2. Оптимизация памяти при работе с большими файлами
3. Добавление поддержки других форматов сжатия
4. Интеграция с PSR-3 для логирования

## Лицензия

MIT License
