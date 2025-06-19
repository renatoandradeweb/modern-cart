# Modern Cart 🛒

[![Latest Stable Version](https://poser.pugx.org/renatoandradeweb/modern-cart/v/stable)](https://packagist.org/packages/renatoandradeweb/modern-cart)
[![Total Downloads](https://poser.pugx.org/renatoandradeweb/modern-cart/downloads)](https://packagist.org/packages/renatoandradeweb/modern-cart)
[![License](https://poser.pugx.org/renatoandradeweb/modern-cart/license)](https://packagist.org/packages/renatoandradeweb/modern-cart)
[![PHP Version Require](https://poser.pugx.org/renatoandradeweb/modern-cart/require/php)](https://packagist.org/packages/renatoandradeweb/modern-cart)
[![Tests](https://github.com/renatoandradeweb/modern-cart/workflows/Tests/badge.svg)](https://github.com/renatoandradeweb/modern-cart/actions)
[![Coverage](https://codecov.io/gh/renatoandradeweb/modern-cart/branch/main/graph/badge.svg)](https://codecov.io/gh/renatoandradeweb/modern-cart)

A modern, type-safe shopping cart library for PHP 8+ with multiple storage backends and a fluent API.

## ✨ Features

- 🔧 **PHP 8+ Features**: Full type declarations, property promotion, match expressions
- 🛡️ **Type Safety**: Strict typing throughout with PHPStan level 9 compliance
- 💾 **Multiple Storage Backends**: Session, Cookie, File, Memory storage
- 🔗 **Fluent API**: Chainable methods for better developer experience
- 📦 **Zero Dependencies**: No external dependencies required
- 🧪 **Fully Tested**: 100% code coverage with PHPUnit
- 📚 **Well Documented**: Comprehensive documentation with examples

## 📋 Requirements

- PHP 8.0 or higher
- Session support (for SessionStore)
- File system access (for FileStore)

## 🚀 Installation

Install via Composer:

```bash
composer require renatoandradeweb/modern-cart
```

## 🎯 Quick Start

```php
<?php

use ModernCart\Cart;
use ModernCart\CartItem;
use ModernCart\Storage\SessionStore;

// Create a cart with session storage
$cart = new Cart('user_123', new SessionStore());

// Add items
$item = new CartItem([
    'name' => 'Awesome Product',
    'price' => 29.99,
    'tax' => 3.00,
    'quantity' => 2
]);

$cart->add($item)->save();

// Get totals
echo "Items: " . $cart->totalItems(); // 2
echo "Subtotal: $" . $cart->subtotal(); // $59.98
echo "Tax: $" . $cart->tax(); // $6.00
echo "Total: $" . $cart->total(); // $65.98
```

## 📖 Usage

### Creating a Cart

```php
use ModernCart\Cart;
use ModernCart\Storage\{SessionStore, CookieStore, FileStore, MemoryStore};

// Session storage (recommended for web applications)
$cart = new Cart('cart_id', new SessionStore());

// Cookie storage (for persistent carts)
$cart = new Cart('cart_id', new CookieStore(
    prefix: 'cart_',
    expiry: 2592000, // 30 days
    path: '/',
    secure: true,
    httpOnly: true
));

// File storage (for server-side persistence)
$cart = new Cart('cart_id', new FileStore('/path/to/storage'));

// Memory storage (for testing)
$cart = new Cart('cart_id', new MemoryStore());
```

### Working with Cart Items

```php
use ModernCart\CartItem;

// Create items
$item = new CartItem([
    'name' => 'Product Name',
    'price' => 19.99,
    'tax' => 2.00,
    'quantity' => 1,
    'sku' => 'PROD-001', // Custom attributes
    'category' => 'Electronics'
]);

// Fluent setters
$item->setName('New Name')
     ->setPrice(24.99)
     ->setQuantity(3);

// Getters
echo $item->getName(); // 'New Name'
echo $item->getPrice(); // 24.99
echo $item->getTotalPrice(); // 80.97 (includes tax)
```

### Cart Operations

```php
// Add items
$cart->add($item);

// Update quantity
$cart->updateQuantity($item->getId(), 5);

// Update any property
$cart->update($item->getId(), 'price', 29.99);

// Remove items
$cart->remove($item->getId());

// Check if item exists
if ($cart->has($item->getId())) {
    // Item exists
}

// Get specific item
$foundItem = $cart->get($item->getId());

// Clear cart
$cart->clear();
```

### Cart Information

```php
// Counts
$cart->totalItems(); // Total quantity of all items
$cart->totalUniqueItems(); // Number of different items
$cart->isEmpty(); // Check if cart is empty
$cart->isNotEmpty(); // Check if cart has items

// Prices
$cart->subtotal(); // Total excluding tax
$cart->tax(); // Total tax
$cart->total(); // Total including tax

// Get all items
$items = $cart->all(); // Returns CartItem[]

// Filter items
$expensiveItems = $cart->filter(fn($item) => $item->getPrice() > 50);

// Find first item matching condition
$firstElectronic = $cart->first(fn($item) => $item->get('category') === 'Electronics');
```

### Persistence

```php
// Manual save
$cart->save();

// Auto-save (saves automatically when cart is destroyed)
// No action needed - cart saves on __destruct if dirty

// Restore from storage
$cart->restore();

// Refresh (discard unsaved changes)
$cart->refresh();

// Check if cart has unsaved changes
if ($cart->isDirty()) {
    $cart->save();
}
```

### Advanced Features

```php
// Copy cart
$newCart = $cart->copy('new_cart_id');

// Merge carts
$cart1->merge($cart2);

// Export as array
$cartData = $cart->toArray();

// Export as JSON
$cartJson = $cart->toJson(JSON_PRETTY_PRINT);

// Get summary
$summary = $cart->summary();
/*
Array:
[
    'id' => 'cart_123',
    'total_items' => 5,
    'unique_items' => 3,
    'subtotal' => 99.95,
    'tax' => 8.00,
    'total' => 107.95,
    'is_empty' => false
]
*/
```

## 🎨 Custom Storage Backend

Implement the `Store` interface to create custom storage:

```php
use ModernCart\Contracts\Store;

class DatabaseStore implements Store
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function get(string $cartId): string
    {
        $stmt = $this->pdo->prepare('SELECT data FROM carts WHERE id = ?');
        $stmt->execute([$cartId]);
        
        return $stmt->fetchColumn() ?: serialize([]);
    }

    public function put(string $cartId, string $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO carts (id, data) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE data = VALUES(data)'
        );
        $stmt->execute([$cartId, $data]);
    }

    public function flush(string $cartId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM carts WHERE id = ?');
        $stmt->execute([$cartId]);
    }

    public function exists(string $cartId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM carts WHERE id = ?');
        $stmt->execute([$cartId]);
        
        return $stmt->fetchColumn() !== false;
    }
}
```

## 🧪 Testing

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis
composer analyse

# Run code style check
composer cs-check

# Fix code style
composer cs-fix

# Run all quality checks
composer quality
```

## 📊 Error Handling

The library uses specific exceptions for different error conditions:

```php
use ModernCart\Exceptions\{
    CartException,
    CartRestoreException,
    InvalidCartItemException
};

try {
    $cart->add($item);
    $cart->save();
} catch (InvalidCartItemException $e) {
    // Handle invalid item data
} catch (CartRestoreException $e) {
    // Handle restoration errors
} catch (CartException $e) {
    // Handle general cart errors
}
```

## 🔧 Configuration

### Session Store Options

```php
$sessionStore = new SessionStore(
    prefix: 'my_cart_' // Default: 'cart_'
);
```

### Cookie Store Options

```php
$cookieStore = new CookieStore(
    prefix: 'cart_',
    expiry: 2592000, // 30 days
    path: '/',
    domain: '',
    secure: false,
    httpOnly: true,
    sameSite: 'Lax'
);
```

### File Store Options

```php
$fileStore = new FileStore(
    storagePath: '/var/cart-storage',
    prefix: 'cart_',
    extension: '.dat'
);

// Clean up old files (older than 30 days)
$cleaned = $fileStore->cleanup(2592000);
```

## 🎭 Migration from Legacy Cart

If migrating from the original library:

```php
// Old way
$cart->add($cartItem);
$existingItem = $cart->find($itemId);
$existingItem->quantity += $cartItem->quantity;

// New way
$cart->add($cartItem); // Automatically handles quantity merging

// Old way
$item->price = 29.99;

// New way
$item->setPrice(29.99);
// or
$cart->update($itemId, 'price', 29.99);
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run quality checks (`composer quality`)
5. Commit your changes (`git commit -am 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Create a Pull Request

## 📜 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🎉 Credits

- Original cart library inspiration
- PHP 8+ modern practices
- Community feedback and contributions

## 📞 Support

- 📧 Email: contato@renatoandradeweb.com.br
- 💬 Discussions: [GitHub Discussions](https://github.com/renatoandradeweb/modern-cart/discussions)
- 🐛 Issues: [GitHub Issues](https://github.com/renatoandradeweb/modern-cart/issues)