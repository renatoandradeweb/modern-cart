<?php

declare(strict_types=1);

namespace ModernCart;

use ModernCart\Contracts\{Arrayable, Store};
use ModernCart\Exceptions\{CartException, CartRestoreException, InvalidCartItemException};

/**
 * Modern Shopping Cart implementation with PHP 8+ features
 */
final class Cart implements Arrayable
{
    /** @var CartItem[] */
    private array $items = [];

    private bool $isDirty = false;

    public function __construct(
        private readonly string $id,
        private readonly Store $store
    ) {
        $this->restore();
    }

    /**
     * Get the cart ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the storage implementation
     */
    public function getStore(): Store
    {
        return $this->store;
    }

    /**
     * Get all items in the cart
     *
     * @return CartItem[]
     */
    public function all(): array
    {
        return array_values($this->items);
    }

    /**
     * Add an item to the cart
     */
    public function add(CartItem $cartItem): self
    {
        $itemId = $cartItem->getId();

        if ($this->has($itemId)) {
            $existingItem = $this->find($itemId);
            $newQuantity = $existingItem->getQuantity() + $cartItem->getQuantity();
            $existingItem->setQuantity($newQuantity);
        } else {
            $this->items[$itemId] = $cartItem;
        }

        $this->markDirty();
        return $this;
    }

    /**
     * Remove an item from the cart
     */
    public function remove(string $itemId): self
    {
        if (isset($this->items[$itemId])) {
            unset($this->items[$itemId]);
            $this->markDirty();
        }

        return $this;
    }

    /**
     * Update an item in the cart
     */
    public function update(string $itemId, string $key, mixed $value): self
    {
        $item = $this->find($itemId);

        if (!$item) {
            throw new InvalidCartItemException("Item [{$itemId}] does not exist in cart");
        }

        $item->set($key, $value);
        $this->markDirty();

        return $this;
    }

    /**
     * Update an item's quantity
     */
    public function updateQuantity(string $itemId, int $quantity): self
    {
        if ($quantity <= 0) {
            return $this->remove($itemId);
        }

        return $this->update($itemId, 'quantity', $quantity);
    }

    /**
     * Get an item from the cart by its ID
     */
    public function get(string $itemId): ?CartItem
    {
        return $this->find($itemId);
    }

    /**
     * Check if an item exists in the cart
     */
    public function has(string $itemId): bool
    {
        return isset($this->items[$itemId]);
    }

    /**
     * Find an item in the cart
     */
    public function find(string $itemId): ?CartItem
    {
        return $this->items[$itemId] ?? null;
    }

    /**
     * Get the first item that matches a condition
     */
    public function first(callable $callback = null): ?CartItem
    {
        if ($callback === null) {
            return $this->items[array_key_first($this->items)] ?? null;
        }

        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Filter items by a condition
     *
     * @return CartItem[]
     */
    public function filter(callable $callback): array
    {
        return array_filter($this->items, $callback);
    }

    /**
     * Transform items using a callback
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }

    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Check if cart is not empty
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    // Count methods

    /**
     * Get the total number of unique items in the cart
     */
    public function totalUniqueItems(): int
    {
        return count($this->items);
    }

    /**
     * Get the total number of items in the cart (sum of quantities)
     */
    public function totalItems(): int
    {
        return array_sum(
            array_map(fn(CartItem $item) => $item->getQuantity(), $this->items)
        );
    }

    /**
     * Alias for totalItems()
     */
    public function count(): int
    {
        return $this->totalItems();
    }

    // Price calculation methods

    /**
     * Get the cart total including tax
     */
    public function total(): float
    {
        return array_sum(
            array_map(fn(CartItem $item) => $item->getTotalPrice(), $this->items)
        );
    }

    /**
     * Get the cart total excluding tax
     */
    public function totalExcludingTax(): float
    {
        return array_sum(
            array_map(fn(CartItem $item) => $item->getTotalPriceExcludingTax(), $this->items)
        );
    }

    /**
     * Get the cart tax total
     */
    public function tax(): float
    {
        return array_sum(
            array_map(fn(CartItem $item) => $item->getTotalTax(), $this->items)
        );
    }

    /**
     * Get the cart subtotal (alias for totalExcludingTax)
     */
    public function subtotal(): float
    {
        return $this->totalExcludingTax();
    }

    // Cart operations

    /**
     * Clear all items from the cart
     */
    public function clear(): self
    {
        $this->items = [];
        $this->store->flush($this->id);
        $this->isDirty = false;

        return $this;
    }

    /**
     * Save the cart state to storage
     */
    public function save(): self
    {
        if (!$this->isDirty) {
            return $this;
        }

        $data = serialize($this->toArray());
        $this->store->put($this->id, $data);
        $this->isDirty = false;

        return $this;
    }

    /**
     * Restore the cart from storage
     */
    public function restore(): self
    {
        try {
            $state = $this->store->get($this->id);

            if (empty($state)) {
                return $this;
            }

            $data = @unserialize($state);

            $this->validateRestoredData($data);
            $this->restoreFromData($data);
            $this->isDirty = false;

        } catch (\Throwable $e) {
            throw new CartRestoreException(
                "Failed to restore cart [{$this->id}]: " . $e->getMessage(),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * Refresh cart from storage (discard unsaved changes)
     */
    public function refresh(): self
    {
        $this->items = [];
        $this->isDirty = false;

        return $this->restore();
    }

    /**
     * Create a copy of this cart with a new ID
     */
    public function copy(string $newId): self
    {
        $newCart = new self($newId, $this->store);

        foreach ($this->items as $item) {
            $newCart->add($item->copy());
        }

        return $newCart;
    }

    /**
     * Merge another cart into this one
     */
    public function merge(Cart $otherCart): self
    {
        foreach ($otherCart->all() as $item) {
            $this->add($item->copy());
        }

        return $this;
    }

    /**
     * Export the cart as an array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'items' => array_map(fn(CartItem $item) => $item->toArray(), $this->items),
            'meta' => [
                'total_items' => $this->totalItems(),
                'unique_items' => $this->totalUniqueItems(),
                'subtotal' => $this->subtotal(),
                'tax' => $this->tax(),
                'total' => $this->total(),
                'is_empty' => $this->isEmpty(),
            ],
        ];
    }

    /**
     * Export cart as JSON
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags);
    }

    /**
     * Get cart summary
     */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'total_items' => $this->totalItems(),
            'unique_items' => $this->totalUniqueItems(),
            'subtotal' => $this->subtotal(),
            'tax' => $this->tax(),
            'total' => $this->total(),
            'is_empty' => $this->isEmpty(),
        ];
    }

    /**
     * Validate restored data structure
     */
    private function validateRestoredData(mixed $data): void
    {
        if ($data === false) {
            throw new CartRestoreException('Saved cart state is not unserializable');
        }

        if (!is_array($data)) {
            throw new CartRestoreException('Unserialized data is not an array');
        }

        if (!isset($data['id']) || !isset($data['items'])) {
            throw new CartRestoreException('Missing cart ID or cart items');
        }

        if (!is_string($data['id']) || !is_array($data['items'])) {
            throw new CartRestoreException('Cart ID must be string and items must be array');
        }

        if ($data['id'] !== $this->id) {
            throw new CartRestoreException('Cart ID mismatch');
        }
    }

    /**
     * Restore cart from validated data
     */
    private function restoreFromData(array $data): void
    {
        $this->items = [];

        foreach ($data['items'] as $itemData) {
            if (!is_array($itemData) || !isset($itemData['data'])) {
                continue; // Skip invalid items
            }

            try {
                $item = new CartItem($itemData['data']);
                $this->items[$item->getId()] = $item;
            } catch (\Throwable $e) {
                // Skip items that can't be restored
                continue;
            }
        }
    }

    /**
     * Mark the cart as dirty (needs saving)
     */
    private function markDirty(): void
    {
        $this->isDirty = true;
    }

    /**
     * Check if cart has unsaved changes
     */
    public function isDirty(): bool
    {
        return $this->isDirty;
    }

    /**
     * Auto-save when cart is destroyed
     */
    public function __destruct()
    {
        if ($this->isDirty) {
            try {
                $this->save();
            } catch (\Throwable $e) {
                // Ignore errors during destruction
            }
        }
    }
}