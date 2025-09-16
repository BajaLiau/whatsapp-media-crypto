<?php
declare(strict_types=1);

namespace WhatsAppMedia\Stream;

use Psr\Http\Message\StreamInterface;

/**
 * Stream decorator that decrypts data using WhatsApp's media encryption protocol
 */
class DecryptingStream extends AbstractCryptoStream
{
    /** @var string Buffer for encrypted data */
    private $encryptedBuffer = '';
    
    /** @var resource|null HMAC context for verification */
    private $macCtx;

    public function __construct(StreamInterface $source, string $cipherKey, string $macKey, string $iv)
    {
        parent::__construct($source, $cipherKey, $macKey, $iv);
        $this->macCtx = hash_init('sha256', HASH_HMAC, $this->macKey);
        hash_update($this->macCtx, $this->iv);
    }

    /**
     * {@inheritdoc}
     */
    public function read($length): string
    {
        $this->produceMore();
        $data = substr($this->buffer, 0, $length);
        $this->buffer = (string)substr($this->buffer, strlen($data));
        return $data === false ? '' : $data;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        return $this->finalized && $this->buffer === '';
    }

    /**
     * Process a chunk of encrypted data for decryption
     *
     * @param string $chunk Encrypted data chunk
     * @return string Decrypted data
     * @throws \RuntimeException If MAC verification fails or decryption fails
     */
    protected function processChunk(string $chunk): string
    {
        if (empty($chunk)) {
            return '';
        }

        if ($this->stream->eof()) {
            // Получаем MAC из последнего чанка
            $mac = substr($chunk, -self::MAC_LEN);
            $data = substr($chunk, 0, -self::MAC_LEN);

            // Обновляем HMAC контекст данными
            hash_update($this->macCtx, $data);

            // Проверяем MAC
            $calculatedMac = substr(hash_final($this->macCtx, true), 0, self::MAC_LEN);
            if (!hash_equals($calculatedMac, $mac)) {
                throw new \RuntimeException('MAC verification failed');
            }

            $decrypted = openssl_decrypt(
                $data,
                'AES-256-CBC',
                $this->cipherKey,
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                $this->iv
            );

            if ($decrypted === false) {
                throw new \RuntimeException('Decryption failed');
            }

            $this->finalized = true;
            return $this->pkcs7_unpad($decrypted);
        }

        // Обновляем HMAC контекст для не финального чанка
        hash_update($this->macCtx, $chunk);

        $decrypted = openssl_decrypt(
            $chunk,
            'AES-256-CBC',
            $this->cipherKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $this->iv
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $decrypted;
    }

    /**
     * Read and process more data from the source stream
     */
    private function produceMore(): void
    {
        if ($this->finalized) {
            return;
        }

        // Накапливаем зашифрованные данные
        while (!$this->stream->eof() && strlen($this->encryptedBuffer) < self::CHUNK_SIZE) {
            $chunk = $this->stream->read(self::CHUNK_SIZE);
            if ($chunk === '') {
                break;
            }
            $this->encryptedBuffer .= $chunk;
        }

        // Обрабатываем полные чанки
        if (strlen($this->encryptedBuffer) >= self::CHUNK_SIZE || $this->stream->eof()) {
            $chunkToProcess = $this->stream->eof() ? $this->encryptedBuffer :
                substr($this->encryptedBuffer, 0, self::CHUNK_SIZE);

            $processed = $this->processChunk($chunkToProcess);
            if (!empty($processed)) {
                $this->buffer .= $processed;
            }

            if (!$this->stream->eof()) {
                $this->encryptedBuffer = substr($this->encryptedBuffer, self::CHUNK_SIZE);
            } else {
                $this->encryptedBuffer = '';
            }
        }
    }
}
