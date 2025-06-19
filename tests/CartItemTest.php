<?php

declare(strict_types=1);

// tests/CartTest.php
namespace ModernCart\Tests;

use PHPUnit\Framework\TestCase;
use ModernCart\Cart;
use ModernCart\CartItem;
use ModernCart\Storage\MemoryStore;
use ModernCart\Exceptions\InvalidCartItemException;

class CartTest extends TestCase
{
    private Cart $cart;
    private MemoryStore $store;

    protected function setUp(): void
    {
        $this->store = new MemoryStore();
        $this->cart = new Cart('test_cart', $this->store);
    }

    public function testCanCreateEmptyCart(): void
    {
        $this->assertTrue($this->cart->isEmpty());
        $this->assertSame(0, $this->cart->totalItems());
        $this->assertSame(0.0, $this->cart->total());
    }

    public function testCanAddItem(): void
    {
        $item = new CartItem([
            'name' => 'Test Product',
            'price' => 19.99,
            'quantity' => 2
        ]);

        $this->cart->add($item);

        $this->assertFalse($this->cart->isEmpty());
        $this->assertSame(2, $this->cart->totalItems());
        $this->assertSame(1, $this->cart->totalUniqueItems());
        $this->assertSame(39.98, $this->cart->total());
    }

    public function testCanAddSameItemMultipleTimes(): void
    {
        $item1 = new CartItem(['name' => 'Product', 'price' => 10.0, 'quantity' => 1]);
        $item2 = new CartItem(['name' => 'Product', 'price' => 10.0, 'quantity' => 2]);

        $this->cart->add($item1)->add($item2);

        $this->assertSame(3, $this->cart->totalItems());
        $this->assertSame(1, $this->cart->totalUniqueItems());
    }

    public function testCanRemoveItem(): void
    {
        $item = new CartItem(['name' => 'Product', 'price' => 10.0]);
        $this->cart->add($item);

        $itemId = $item->getId();
        $this->assertTrue($this->cart->has($itemId));

        $this->cart->remove($itemId);
        $this->assertFalse($this->cart->has($itemId));
        $this->assertTrue($this->cart->isEmpty());
    }

    public function testCanUpdateItemQuantity(): void
    {
        $item = new CartItem(['name' => 'Product', 'price' => 10.0, 'quantity' => 1]);
        $this->cart->add($item);

        $this->cart->updateQuantity($item->getId(), 5);

        $updatedItem = $this->cart->get($item->getId());
        $this->assertSame(5, $updatedItem->getQuantity());
        $this->assertSame(5, $this->cart->totalItems());
    }

    public function testCanUpdateItemPrice(): void
    {
        $item = new CartItem(['name' => 'Product', 'price' => 10.0]);
        $this->cart->add($item);

        $this->cart->update($item->getId(), 'price', 15.0);

        $updatedItem = $this->cart->get($item->getId());
        $this->assertSame(15.0, $updatedItem->getPrice());
    }

    public function testCanClearCart(): void
    {
        $item = new CartItem(['name' => 'Product', 'price' => 10.0]);
        $this->cart->add($item);

        $this->assertFalse($this->cart->isEmpty());

        $this->cart->clear();

        $this->assertTrue($this->cart->isEmpty());
        $this->assertSame(0, $this->cart->totalItems());
    }

    public function testCanCalculateTotals(): void
    {
        $item1 = new CartItem(['name' => 'Product 1', 'price' => 10.0, 'tax' => 1.0, 'quantity' => 2]);
        $item2 = new CartItem(['name' => 'Product 2', 'price' => 15.0, 'tax' => 1.5, 'quantity' => 1]);

        $this->cart->add($item1)->add($item2);

        $this->assertSame(25.0, $this->cart->subtotal()); // (10*2) + (15*1)
        $this->assertSame(3.5, $this->cart->tax()); // (1*2) + (1.5*1)
        $this->assertSame(28.5, $this->cart->total()); // subtotal + tax
    }

    public function testCanSaveAndRestore(): void
    {
        $item = new CartItem(['name' => 'Product', 'price' => 10.0, 'quantity' => 2]);
        $this->cart->add($item);
        $this->cart->save();

        // Create new cart instance with same ID
        $newCart = new Cart('test_cart', $this->store);

        $this->assertSame(2, $newCart->totalItems());
        $this->assertSame(20.0, $newCart->total());
    }

    public function testCanFilterItems(): void
    {
        $item1 = new CartItem(['name' => 'Cheap', 'price' => 5.0]);
        $item2 = new CartItem(['name' => 'Expensive', 'price' => 50.0]);

        $this->cart->add($item1)->add($item2);

        $expensiveItems = $this->cart->filter(fn($item) => $item->getPrice() > 10);

        $this->assertCount(1, $expensiveItems);
        $this->assertSame('Expensive', reset($expensiveItems)->getName());
    }

    public function testCanCopyCart(): void
    {
        $item = new CartItem(['name' => 'Product', 'price' => 10.0]);
        $this->cart->add($item);

        $copiedCart = $this->cart->copy('copied_cart');

        $this->assertSame('copied_cart', $copiedCart->getId());
        $this->assertSame(1, $copiedCart->totalItems());
        $this->assertNotSame($this->cart, $copiedCart);
    }

    public function testCanMergeCarts(): void
    {
        $item1 = new CartItem(['name' => 'Product 1', 'price' => 10.0]);
        $item2 = new CartItem(['name' => 'Product 2', 'price' => 15.0]);

        $this->cart->add($item1);

        $otherCart = new Cart('other_cart', new MemoryStore());
        $otherCart->add($item2);

        $this->cart->merge($otherCart);

        $this->assertSame(2, $this->cart->totalUniqueItems());
        $this->assertSame(25.0, $this->cart->subtotal());
    }

    public function testExportsToArray(): void
    {
        $item = new CartItem(['name' => 'Product', 'price' => 10.0, 'quantity' => 2]);
        $this->cart->add($item);

        $array = $this->cart->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('items', $array);
        $this->assertArrayHasKey('meta', $array);
        $this->assertSame('test_cart', $array['id']);
        $this->assertCount(1, $array['items']);
        $this->assertSame(2, $array['meta']['total_items']);
    }

    public function testCanGetSummary(): void
    {
        $item = new CartItem(['name' => 'Product', 'price' => 10.0, 'tax' => 1.0, 'quantity' => 2]);
        $this->cart->add($item);

        $summary = $this->cart->summary();

        $expected = [
            'id' => 'test_cart',
            'total_items' => 2,
            'unique_items' => 1,
            'subtotal' => 20.0,
            'tax' => 2.0,
            'total' => 22.0,
            'is_empty' => false,
        ];

        $this->assertSame($expected, $summary);
    }
}