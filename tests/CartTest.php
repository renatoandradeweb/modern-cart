<?php
// tests/CartItemTest.php
namespace ModernCart\Tests;

use PHPUnit\Framework\TestCase;
use ModernCart\CartItem;
use ModernCart\Exceptions\InvalidCartItemException;

class CartItemTest extends TestCase
{
    public function testCanCreateItemWithDefaults(): void
    {
        $item = new CartItem();

        $this->assertSame('', $item->getName());
        $this->assertSame(1, $item->getQuantity());
        $this->assertSame(0.0, $item->getPrice());
        $this->assertSame(0.0, $item->getTax());
    }

    public function testCanCreateItemWithData(): void
    {
        $data = [
            'name' => 'Test Product',
            'price' => 19.99,
            'tax' => 2.0,
            'quantity' => 3,
            'sku' => 'TEST-001'
        ];

        $item = new CartItem($data);

        $this->assertSame('Test Product', $item->getName());
        $this->assertSame(19.99, $item->getPrice());
        $this->assertSame(2.0, $item->getTax());
        $this->assertSame(3, $item->getQuantity());
        $this->assertSame('TEST-001', $item->get('sku'));
    }

    public function testGeneratesConsistentId(): void
    {
        $data = ['name' => 'Product', 'price' => 10.0];

        $item1 = new CartItem($data);
        $item2 = new CartItem($data);

        $this->assertSame($item1->getId(), $item2->getId());
    }

    public function testQuantityDoesNotAffectId(): void
    {
        $item1 = new CartItem(['name' => 'Product', 'price' => 10.0, 'quantity' => 1]);
        $item2 = new CartItem(['name' => 'Product', 'price' => 10.0, 'quantity' => 5]);

        $this->assertSame($item1->getId(), $item2->getId());
    }

    public function testCannotSetNegativeQuantity(): void
    {
        $this->expectException(InvalidCartItemException::class);

        $item = new CartItem();
        $item->setQuantity(-1);
    }

    public function testCannotSetNegativePrice(): void
    {
        $this->expectException(InvalidCartItemException::class);

        $item = new CartItem();
        $item->setPrice(-10.0);
    }

    public function testCannotSetNegativeTax(): void
    {
        $this->expectException(InvalidCartItemException::class);

        $item = new CartItem();
        $item->setTax(-1.0);
    }

    public function testCalculatesTotalPriceCorrectly(): void
    {
        $item = new CartItem([
            'price' => 10.0,
            'tax' => 1.0,
            'quantity' => 3
        ]);

        $this->assertSame(33.0, $item->getTotalPrice()); // (10 + 1) * 3
        $this->assertSame(30.0, $item->getTotalPriceExcludingTax()); // 10 * 3
        $this->assertSame(3.0, $item->getTotalTax()); // 1 * 3
    }

    public function testCalculatesSinglePriceCorrectly(): void
    {
        $item = new CartItem([
            'price' => 10.0,
            'tax' => 1.0
        ]);

        $this->assertSame(11.0, $item->getSinglePrice()); // 10 + 1
        $this->assertSame(10.0, $item->getSinglePriceExcludingTax()); // 10
        $this->assertSame(1.0, $item->getSingleTax()); // 1
    }

    public function testArrayAccessInterface(): void
    {
        $item = new CartItem(['name' => 'Product', 'price' => 10.0]);

        // Test isset
        $this->assertTrue(isset($item['name']));
        $this->assertTrue(isset($item['price']));
        $this->assertFalse(isset($item['nonexistent']));

        // Test get
        $this->assertSame('Product', $item['name']);
        $this->assertSame(10.0, $item['price']);

        // Test set
        $item['name'] = 'Updated Product';
        $this->assertSame('Updated Product', $item['name']);

        // Test custom property
        $item['category'] = 'Electronics';
        $this->assertSame('Electronics', $item['category']);
    }

    public function testMagicMethods(): void
    {
        $item = new CartItem();

        // Test __set and __get
        $item->name = 'Magic Product';
        $this->assertSame('Magic Product', $item->name);

        // Test __isset
        $this->assertTrue(isset($item->name));
        $this->assertFalse(isset($item->nonexistent));

        // Test custom property
        $item->custom = 'value';
        $this->assertSame('value', $item->custom);
    }

    public function testCanCopyItem(): void
    {
        $original = new CartItem(['name' => 'Product', 'price' => 10.0]);
        $copy = $original->copy();

        $this->assertNotSame($original, $copy);
        $this->assertSame($original->getName(), $copy->getName());
        $this->assertSame($original->getPrice(), $copy->getPrice());
        $this->assertSame($original->getId(), $copy->getId());
    }

    public function testExportsToArray(): void
    {
        $item = new CartItem([
            'name' => 'Product',
            'price' => 10.0,
            'quantity' => 2
        ]);

        $array = $item->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertIsString($array['id']);
        $this->assertIsArray($array['data']);
    }
}