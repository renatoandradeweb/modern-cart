<?php
// src/Exceptions/InvalidCartItemException.php
namespace ModernCart\Exceptions;

class InvalidCartItemException extends CartException
{
    public static function negativeQuantity(): self
    {
        return new self('Cart item quantity cannot be negative');
    }

    public static function negativePrice(): self
    {
        return new self('Cart item price cannot be negative');
    }

    public static function negativeTax(): self
    {
        return new self('Cart item tax cannot be negative');
    }

    public static function readOnlyProperty(string $property): self
    {
        return new self("Cannot modify read-only property: {$property}");
    }

    public static function requiredProperty(string $property): self
    {
        return new self("Cannot remove required property: {$property}");
    }
}