<?php
// src/Storage/FileStore.php
namespace ModernCart\Storage;

use ModernCart\Contracts\Store;
use ModernCart\Exceptions\CartException;

/**
 * File-based cart storage
 */
final class FileStore implements Store
{
    public function __construct(
        private string $storagePath,
        private string $prefix = 'cart_',
        private string $extension = '.dat'
    ) {
        $this->ensureDirectoryExists();
    }

    public function get(string $cartId): string
    {
        $filepath = $this->getFilePath($cartId);

        if (!file_exists($filepath)) {
            return serialize([]);
        }

        $data = file_get_contents($filepath);

        if ($data === false) {
            throw new CartException("Failed to read cart file: {$filepath}");
        }

        return $data;
    }

    public function put(string $cartId, string $data): void
    {
        $filepath = $this->getFilePath($cartId);

        if (file_put_contents($filepath, $data, LOCK_EX) === false) {
            throw new CartException("Failed to write cart file: {$filepath}");
        }
    }

    public function flush(string $cartId): void
    {
        $filepath = $this->getFilePath($cartId);

        if (file_exists($filepath) && !unlink($filepath)) {
            throw new CartException("Failed to delete cart file: {$filepath}");
        }
    }

    public function exists(string $cartId): bool
    {
        return file_exists($this->getFilePath($cartId));
    }

    private function getFilePath(string $cartId): string
    {
        $filename = $this->prefix . preg_replace('/[^a-zA-Z0-9_-]/', '_', $cartId) . $this->extension;
        return rtrim($this->storagePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    }

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->storagePath)) {
            if (!mkdir($this->storagePath, 0755, true)) {
                throw new CartException("Failed to create storage directory: {$this->storagePath}");
            }
        }

        if (!is_writable($this->storagePath)) {
            throw new CartException("Storage directory is not writable: {$this->storagePath}");
        }
    }

    /**
     * Clean up old cart files
     */
    public function cleanup(int $maxAge = 2592000): int
    {
        $cleaned = 0;
        $cutoff = time() - $maxAge;

        $files = glob($this->storagePath . DIRECTORY_SEPARATOR . $this->prefix . '*' . $this->extension);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }
}