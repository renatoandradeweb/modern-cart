<?php
// src/Storage/MemoryStore.php
namespace ModernCart\Storage;

use ModernCart\Contracts\Store;

/**
 * In-memory cart storage (for testing/temporary use)
 */
final class MemoryStore implements Store
{
    /** @var array<string, string> */
    private array $storage = [];

    public function get(string $cartId): string
    {
        return $this->storage[$cartId] ?? serialize([]);
    }

    public function put(string $cartId, string $data): void
    {
        $this->storage[$cartId] = $data;
    }

    public function flush(string $cartId): void
    {
        unset($this->storage[$cartId]);
    }

    public function exists(string $cartId): bool
    {
        return isset($this->storage[$cartId]);
    }

    /**
     * Clear all stored data
     */
    public function clear(): void
    {
        $this->storage = [];
    }

    /**
     * Get all stored cart IDs
     */
    public function getAllCartIds(): array
    {
        return array_keys($this->storage);
    }

    /**
     * Get the number of stored carts
     */
    public function count(): int
    {
        return count($this->storage);
    }
}