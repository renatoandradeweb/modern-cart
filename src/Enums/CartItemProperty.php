<?php
// src/Enums/CartItemProperty.php (PHP 8.1+)
namespace ModernCart\Enums;

enum CartItemProperty: string
{
    case ID = 'id';
    case NAME = 'name';
    case QUANTITY = 'quantity';
    case PRICE = 'price';
    case TAX = 'tax';

    /**
     * Get all property names as array
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    /**
     * Check if property is read-only
     */
    public function isReadOnly(): bool
    {
        return $this === self::ID;
    }

    /**
     * Check if property is required
     */
    public function isRequired(): bool
    {
        return match ($this) {
            self::ID, self::QUANTITY, self::PRICE, self::TAX => true,
            default => false
        };
    }
}