<?php
// src/Storage/CookieStore.php
namespace ModernCart\Storage;

use ModernCart\Contracts\Store;

/**
 * Cookie-based cart storage
 */
final class CookieStore implements Store
{
    public function __construct(
        private string $prefix = 'cart_',
        private int $expiry = 2592000, // 30 days
        private string $path = '/',
        private string $domain = '',
        private bool $secure = false,
        private bool $httpOnly = true,
        private string $sameSite = 'Lax'
    ) {}

    public function get(string $cartId): string
    {
        $key = $this->getKey($cartId);
        $data = $_COOKIE[$key] ?? '';

        return $data ? $this->decode($data) : serialize([]);
    }

    public function put(string $cartId, string $data): void
    {
        $key = $this->getKey($cartId);
        $encodedData = $this->encode($data);

        $this->setCookie($key, $encodedData);
    }

    public function flush(string $cartId): void
    {
        $key = $this->getKey($cartId);
        $this->unsetCookie($key);
    }

    public function exists(string $cartId): bool
    {
        $key = $this->getKey($cartId);
        return isset($_COOKIE[$key]);
    }

    private function getKey(string $cartId): string
    {
        return $this->prefix . $cartId;
    }

    private function encode(string $data): string
    {
        return base64_encode($data);
    }

    private function decode(string $data): string
    {
        $decoded = base64_decode($data, true);
        return $decoded !== false ? $decoded : serialize([]);
    }

    private function setCookie(string $name, string $data): void
    {
        $options = [
            'expires' => time() + $this->expiry,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite,
        ];

        setcookie($name, $data, $options);
    }

    private function unsetCookie(string $name): void
    {
        $options = [
            'expires' => time() - 3600,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite,
        ];

        setcookie($name, '', $options);
        unset($_COOKIE[$name]);
    }
}