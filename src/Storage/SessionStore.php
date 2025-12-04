<?php
declare(strict_types=1);

// src/Storage/SessionStore.php
namespace ModernCart\Storage;

use ModernCart\Contracts\Store;

/**
 * Session-based cart storage
 */
final class SessionStore implements Store
{
    public function __construct(
        private string $prefix = 'cart_'
    )
    {
        $this->ensureSessionStarted();
    }

    public function get(string $cartId): string
    {
        $key = $this->getKey($cartId);
        return $_SESSION[$key] ?? serialize([]);
    }

    public function put(string $cartId, string $data): void
    {
        $key = $this->getKey($cartId);
        $_SESSION[$key] = $data;
    }

    public function flush(string $cartId): void
    {
        $key = $this->getKey($cartId);
        unset($_SESSION[$key]);
    }

    public function exists(string $cartId): bool
    {
        $key = $this->getKey($cartId);
        return isset($_SESSION[$key]);
    }

    private function getKey(string $cartId): string
    {
        return $this->prefix . $cartId;
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}