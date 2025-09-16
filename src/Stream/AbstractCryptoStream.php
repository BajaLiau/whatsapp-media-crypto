<?php
declare(strict_types=1);

namespace WhatsAppMedia\Stream;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\StreamDecoratorTrait;

/**
 * Base class for WhatsApp media encryption/decryption streams
 */
abstract class AbstractCryptoStream implements StreamInterface
{
    use StreamDecoratorTrait;

    protected const CHUNK_SIZE = 65536;
    protected const MAC_LEN = 10;

    /** @var string */
    protected $cipherKey;

    /** @var string */
    protected $macKey;

    /** @var string */
    protected $iv;

    /** @var string */
    protected $buffer = '';

    /** @var bool */
    protected $finalized = false;

    /**
     * @param StreamInterface $source Source stream to encrypt/decrypt
     * @param string $cipherKey Key for AES-CBC encryption/decryption
     * @param string $macKey Key for HMAC calculation
     * @param string $iv Initialization vector
     */
    public function __construct(
        StreamInterface $source,
        string $cipherKey,
        string $macKey,
        string $iv
    ) {
        $this->stream = $source;
        $this->cipherKey = $cipherKey;
        $this->macKey = $macKey;
        $this->iv = $iv;
    }

    /**
     * Process a chunk of data
     *
     * @param string $chunk Raw data chunk to process
     * @return string Processed data
     */
    abstract protected function processChunk(string $chunk): string;

    /**
     * Add PKCS7 padding to the data
     *
     * @param string $data Data to pad
     * @param int $blockSize Block size for padding
     * @return string Padded data
     */
    protected function pkcs7_pad(string $data, int $blockSize = 16): string
    {
        $padLen = $blockSize - (strlen($data) % $blockSize);
        return $data . str_repeat(chr($padLen), $padLen);
    }

    /**
     * Remove PKCS7 padding from the data
     *
     * @param string $data Padded data
     * @return string Unpadded data
     * @throws \RuntimeException If padding is invalid
     */
    protected function pkcs7_unpad(string $data): string
    {
        $padLen = ord($data[strlen($data) - 1]);
        if ($padLen < 1 || $padLen > 16) {
            throw new \RuntimeException('Invalid PKCS7 padding');
        }
        return substr($data, 0, -$padLen);
    }

    /**
     * Read from the stream and fill buffer
     *
     * @param int $length Number of bytes to read
     * @return string
     */
    public function read($length): string
    {
        throw new \BadMethodCallException('Must be implemented in subclass');
    }

    /**
     * Check if stream has reached EOF
     */
    public function eof(): bool
    {
        return ($this->finalized || $this->stream->eof()) && $this->buffer === '';
    }
}
