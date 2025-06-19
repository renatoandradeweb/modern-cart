<?php
// src/Contracts/Store.php
namespace ModernCart\Contracts;

interface Store
{
    /**
     * Retrieve the saved state for a cart instance.
     */
    public function get(string $cartId): string;

    /**
     * Save the state for a cart instance.
     */
    public function put(string $cartId, string $data): void;

    /**
     * Flush the saved state for a cart instance.
     */
    public function flush(string $cartId): void;

    /**
     * Check if a cart exists in storage.
     */
    public function exists(string $cartId): bool;
}