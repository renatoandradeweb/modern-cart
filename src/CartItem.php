<?php

declare(strict_types=1);

namespace ModernCart;

use ModernCart\Contracts\Arrayable;
use ModernCart\Exceptions\InvalidCartItemException;
use ArrayAccess;

/**
 * Modern Cart Item implementation with PHP 8+ features
 *
 * @implements ArrayAccess<string, mixed>
 */
final class CartItem implements ArrayAccess, Arrayable
{
    private array $data;
    private ?string $cachedId = null;

    public function __construct(array $data = [])
    {
        $defaults = [
            'name' => '',
            'quantity' => 1,
            'price' => 0.00,
            'tax' => 0.00,
        ];

        $this->data = array_merge($defaults, $data);
        $this->validateData();
    }

    /**
     * Create a cart item from array data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Get the unique cart item ID based on item data (excluding quantity)
     */
    public function getId(): string
    {
        if ($this->cachedId !== null) {
            return $this->cachedId;
        }

        // Keys to ignore in the hashing process
        $ignoreKeys = ['quantity'];

        // Data to use for the hashing process
        $hashData = $this->data;
        foreach ($ignoreKeys as $key) {
            unset($hashData[$key]);
        }

        $this->cachedId = hash('sha256', serialize($hashData));

        return $this->cachedId;
    }

    /**
     * Get item name
     */
    public function getName(): string
    {
        return (string) ($this->data['name'] ?? '');
    }

    /**
     * Set item name
     */
    public function setName(string $name): self
    {
        $this->data['name'] = $name;
        $this->invalidateCache();
        return $this;
    }

    /**
     * Get quantity
     */
    public function getQuantity(): int
    {
        return (int) $this->data['quantity'];
    }

    /**
     * Set quantity
     */
    public function setQuantity(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidCartItemException('Quantity must be non-negative');
        }

        $this->data['quantity'] = $quantity;
        return $this;
    }

    /**
     * Get price per unit (excluding tax)
     */
    public function getPrice(): float
    {
        return (float) $this->data['price'];
    }

    /**
     * Set price per unit
     */
    public function setPrice(float $price): self
    {
        if ($price < 0) {
            throw new InvalidCartItemException('Price must be non-negative');
        }

        $this->data['price'] = $price;
        $this->invalidateCache();
        return $this;
    }

    /**
     * Get tax per unit
     */
    public function getTax(): float
    {
        return (float) $this->data['tax'];
    }

    /**
     * Set tax per unit
     */
    public function setTax(float $tax): self
    {
        if ($tax < 0) {
            throw new InvalidCartItemException('Tax must be non-negative');
        }

        $this->data['tax'] = $tax;
        $this->invalidateCache();
        return $this;
    }

    /**
     * Get a custom data value
     */
    public function get(string $key): mixed
    {
        return match ($key) {
            'id' => $this->getId(),
            'name' => $this->getName(),
            'quantity' => $this->getQuantity(),
            'price' => $this->getPrice(),
            'tax' => $this->getTax(),
            default => $this->data[$key] ?? null
        };
    }

    /**
     * Set a custom data value
     */
    public function set(string $key, mixed $value): self
    {
        switch ($key) {
            case 'id':
                throw new InvalidCartItemException('Cannot manually set item ID');
            case 'name':
                return $this->setName((string) $value);
            case 'quantity':
                return $this->setQuantity((int) $value);
            case 'price':
                return $this->setPrice((float) $value);
            case 'tax':
                return $this->setTax((float) $value);
            default:
                $this->data[$key] = $value;
                $this->invalidateCache();
                return $this;
        }
    }

    /**
     * Check if a data key exists
     */
    public function has(string $key): bool
    {
        return match ($key) {
            'id', 'name', 'quantity', 'price', 'tax' => true,
            default => array_key_exists($key, $this->data)
        };
    }

    /**
     * Remove a custom data key
     */
    public function remove(string $key): self
    {
        if (in_array($key, ['id', 'name', 'quantity', 'price', 'tax'], true)) {
            throw new InvalidCartItemException("Cannot remove required property: {$key}");
        }

        unset($this->data[$key]);
        $this->invalidateCache();
        return $this;
    }

    // Price calculation methods

    /**
     * Get the total price including tax for all quantities
     */
    public function getTotalPrice(): float
    {
        return ($this->getPrice() + $this->getTax()) * $this->getQuantity();
    }

    /**
     * Get the total price excluding tax for all quantities
     */
    public function getTotalPriceExcludingTax(): float
    {
        return $this->getPrice() * $this->getQuantity();
    }

    /**
     * Get the single price including tax
     */
    public function getSinglePrice(): float
    {
        return $this->getPrice() + $this->getTax();
    }

    /**
     * Get the single price excluding tax
     */
    public function getSinglePriceExcludingTax(): float
    {
        return $this->getPrice();
    }

    /**
     * Get the total tax for all quantities
     */
    public function getTotalTax(): float
    {
        return $this->getTax() * $this->getQuantity();
    }

    /**
     * Get the single tax value
     */
    public function getSingleTax(): float
    {
        return $this->getTax();
    }

    // ArrayAccess implementation

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string) $offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove((string) $offset);
    }

    // Magic methods

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __unset(string $key): void
    {
        $this->remove($key);
    }

    /**
     * Export the cart item as an array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'data' => $this->data,
        ];
    }

    /**
     * Get all data as array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Create a copy of this cart item
     */
    public function copy(): self
    {
        return new self($this->data);
    }

    /**
     * Validate the cart item data
     */
    private function validateData(): void
    {
        if ($this->getQuantity() < 0) {
            throw new InvalidCartItemException('Quantity must be non-negative');
        }

        if ($this->getPrice() < 0) {
            throw new InvalidCartItemException('Price must be non-negative');
        }

        if ($this->getTax() < 0) {
            throw new InvalidCartItemException('Tax must be non-negative');
        }
    }

    /**
     * Invalidate the cached ID when data changes
     */
    private function invalidateCache(): void
    {
        $this->cachedId = null;
    }
}