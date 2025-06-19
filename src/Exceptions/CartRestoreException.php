<?php
// src/Exceptions/CartRestoreException.php
namespace ModernCart\Exceptions;

class CartRestoreException extends CartException
{
    public static function unserializeFailed(): self
    {
        return new self('Failed to unserialize cart data');
    }

    public static function invalidData(string $reason): self
    {
        return new self("Invalid cart data: {$reason}");
    }

    public static function corruptedData(): self
    {
        return new self('Cart data appears to be corrupted');
    }
}