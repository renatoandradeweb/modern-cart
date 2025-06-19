<?php

declare(strict_types=1);

// examples/ecommerce-example.php

require_once __DIR__ . '/../vendor/autoload.php';

use ModernCart\Cart;
use ModernCart\CartItem;
use ModernCart\Storage\SessionStore;

/**
 * Exemplo prático de uso da biblioteca Modern Cart
 * em um sistema de e-commerce
 */
class EcommerceExample
{
    private Cart $cart;

    public function __construct(string $userId)
    {
        // Inicializar carrinho com storage de sessão
        $this->cart = new Cart("user_{$userId}", new SessionStore('ecommerce_cart_'));
    }

    /**
     * Adicionar produto ao carrinho
     */
    public function addProduct(array $productData): void
    {
        $item = new CartItem([
            'name' => $productData['name'],
            'price' => $productData['price'],
            'tax' => $this->calculateTax($productData['price']),
            'quantity' => $productData['quantity'] ?? 1,
            // Dados personalizados do produto
            'sku' => $productData['sku'],
            'category' => $productData['category'],
            'image' => $productData['image'],
            'weight' => $productData['weight'] ?? 0,
            'dimensions' => $productData['dimensions'] ?? [],
        ]);

        $this->cart->add($item);
        $this->cart->save();

        echo "✅ Produto '{$item->getName()}' adicionado ao carrinho!\n";
    }

    /**
     * Remover produto do carrinho
     */
    public function removeProduct(string $sku): void
    {
        $item = $this->findItemBySku($sku);

        if ($item) {
            $this->cart->remove($item->getId());
            $this->cart->save();
            echo "🗑️ Produto removido do carrinho!\n";
        } else {
            echo "❌ Produto não encontrado no carrinho!\n";
        }
    }

    /**
     * Atualizar quantidade de um produto
     */
    public function updateQuantity(string $sku, int $quantity): void
    {
        $item = $this->findItemBySku($sku);

        if ($item) {
            if ($quantity <= 0) {
                $this->removeProduct($sku);
            } else {
                $this->cart->updateQuantity($item->getId(), $quantity);
                $this->cart->save();
                echo "🔄 Quantidade atualizada para {$quantity}!\n";
            }
        } else {
            echo "❌ Produto não encontrado no carrinho!\n";
        }
    }

    /**
     * Aplicar cupom de desconto (exemplo simplificado)
     */
    public function applyCoupon(string $couponCode): void
    {
        $discount = $this->validateCoupon($couponCode);

        if ($discount > 0) {
            // Adicionar desconto como item negativo
            $discountItem = new CartItem([
                'name' => "Desconto - {$couponCode}",
                'price' => -($this->cart->subtotal() * $discount / 100),
                'tax' => 0,
                'quantity' => 1,
                'type' => 'discount',
                'coupon_code' => $couponCode,
            ]);

            $this->cart->add($discountItem);
            $this->cart->save();

            echo "🎉 Cupom '{$couponCode}' aplicado! Desconto de {$discount}%\n";
        } else {
            echo "❌ Cupom inválido!\n";
        }
    }

    /**
     * Calcular frete
     */
    public function calculateShipping(string $zipCode): float
    {
        $totalWeight = 0;
        $hasFragileItems = false;

        foreach ($this->cart->all() as $item) {
            $totalWeight += $item->get('weight') * $item->getQuantity();

            if (str_contains(strtolower($item->get('category')), 'fragil')) {
                $hasFragileItems = true;
            }
        }

        // Lógica simplificada de cálculo de frete
        $baseRate = 10.0;
        $weightRate = $totalWeight * 0.5;
        $fragileRate = $hasFragileItems ? 5.0 : 0;

        return $baseRate + $weightRate + $fragileRate;
    }

    /**
     * Adicionar frete ao carrinho
     */
    public function addShipping(string $zipCode): void
    {
        // Remover frete anterior se existir
        $this->removeShipping();

        $shippingCost = $this->calculateShipping($zipCode);

        $shippingItem = new CartItem([
            'name' => 'Frete',
            'price' => $shippingCost,
            'tax' => 0,
            'quantity' => 1,
            'type' => 'shipping',
            'zip_code' => $zipCode,
        ]);

        $this->cart->add($shippingItem);
        $this->cart->save();

        echo "🚚 Frete calculado: R$ " . number_format($shippingCost, 2, ',', '.') . "\n";
    }

    /**
     * Remover frete do carrinho
     */
    public function removeShipping(): void
    {
        $shippingItems = $this->cart->filter(fn($item) => $item->get('type') === 'shipping');

        foreach ($shippingItems as $item) {
            $this->cart->remove($item->getId());
        }
    }

    /**
     * Exibir resumo do carrinho
     */
    public function displaySummary(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🛒 RESUMO DO CARRINHO\n";
        echo str_repeat("=", 60) . "\n";

        if ($this->cart->isEmpty()) {
            echo "Carrinho vazio\n";
            return;
        }

        // Agrupar itens por tipo
        $products = [];
        $discounts = [];
        $shipping = [];

        foreach ($this->cart->all() as $item) {
            $type = $item->get('type') ?? 'product';

            switch ($type) {
                case 'discount':
                    $discounts[] = $item;
                    break;
                case 'shipping':
                    $shipping[] = $item;
                    break;
                default:
                    $products[] = $item;
            }
        }

        // Exibir produtos
        echo "PRODUTOS:\n";
        echo str_repeat("-", 60) . "\n";
        foreach ($products as $item) {
            $name = $item->getName();
            $qty = $item->getQuantity();
            $price = $item->getPrice();
            $total = $item->getTotalPriceExcludingTax();

            echo sprintf("%-30s %2dx R$ %8.2f = R$ %8.2f\n",
                substr($name, 0, 30), $qty, $price, $total);
        }

        // Exibir descontos
        if (!empty($discounts)) {
            echo "\nDESCONTOS:\n";
            echo str_repeat("-", 60) . "\n";
            foreach ($discounts as $item) {
                $name = $item->getName();
                $value = abs($item->getPrice());
                echo sprintf("%-40s -R$ %8.2f\n", $name, $value);
            }
        }

        // Exibir frete
        if (!empty($shipping)) {
            echo "\nFRETE:\n";
            echo str_repeat("-", 60) . "\n";
            foreach ($shipping as $item) {
                $name = $item->getName();
                $value = $item->getPrice();
                echo sprintf("%-40s  R$ %8.2f\n", $name, $value);
            }
        }

        // Totais
        echo "\n" . str_repeat("-", 60) . "\n";
        echo sprintf("Subtotal: %44s R$ %8.2f\n", "", $this->cart->subtotal());
        echo sprintf("Impostos: %44s R$ %8.2f\n", "", $this->cart->tax());
        echo sprintf("TOTAL: %47s R$ %8.2f\n", "", $this->cart->total());
        echo str_repeat("=", 60) . "\n";
    }

    /**
     * Finalizar compra
     */
    public function checkout(): array
    {
        if ($this->cart->isEmpty()) {
            throw new \InvalidArgumentException('Carrinho está vazio');
        }

        $orderData = [
            'cart_id' => $this->cart->getId(),
            'items' => $this->cart->toArray()['items'],
            'summary' => $this->cart->summary(),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Limpar carrinho após finalizar
        $this->cart->clear();

        echo "🎉 Pedido finalizado com sucesso!\n";
        echo "Total: R$ " . number_format($orderData['summary']['total'], 2, ',', '.') . "\n";

        return $orderData;
    }

    /**
     * Salvar carrinho para mais tarde
     */
    public function saveForLater(): string
    {
        $savedCartId = 'saved_' . $this->cart->getId() . '_' . time();
        $savedCart = $this->cart->copy($savedCartId);
        $savedCart->save();

        echo "💾 Carrinho salvo para mais tarde!\n";
        echo "ID: {$savedCartId}\n";

        return $savedCartId;
    }

    /**
     * Métodos auxiliares
     */
    private function findItemBySku(string $sku): ?CartItem
    {
        return $this->cart->first(fn($item) => $item->get('sku') === $sku);
    }

    private function calculateTax(float $price): float
    {
        // Cálculo simplificado de imposto (ICMS 18%)
        return $price * 0.18;
    }

    private function validateCoupon(string $code): float
    {
        // Validação simplificada de cupons
        $validCoupons = [
            'DESC10' => 10.0,
            'DESC20' => 20.0,
            'BLACKFRIDAY' => 30.0,
        ];

        return $validCoupons[strtoupper($code)] ?? 0;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }
}

// Exemplo de uso
if (php_sapi_name() === 'cli') {
    echo "🛍️ Exemplo de E-commerce com Modern Cart\n";
    echo str_repeat("=", 50) . "\n\n";

    // Inicializar carrinho para usuário
    $ecommerce = new EcommerceExample('12345');

    // Adicionar produtos
    $ecommerce->addProduct([
        'name' => 'Smartphone Galaxy S23',
        'price' => 1299.99,
        'sku' => 'GALAXY-S23',
        'category' => 'Eletrônicos',
        'image' => 'galaxy-s23.jpg',
        'weight' => 0.168,
        'quantity' => 1
    ]);

    $ecommerce->addProduct([
        'name' => 'Fone Bluetooth AirPods',
        'price' => 899.99,
        'sku' => 'AIRPODS-PRO',
        'category' => 'Acessórios',
        'image' => 'airpods.jpg',
        'weight' => 0.056,
        'quantity' => 2
    ]);

    $ecommerce->addProduct([
        'name' => 'Capa Protetora Frágil',
        'price' => 49.99,
        'sku' => 'CAPA-FRAGIL',
        'category' => 'Acessórios Frágil',
        'image' => 'capa.jpg',
        'weight' => 0.025,
        'quantity' => 1
    ]);

    // Exibir carrinho inicial
    $ecommerce->displaySummary();

    echo "\n📝 Operações no carrinho:\n";
    echo str_repeat("-", 30) . "\n";

    // Atualizar quantidade
    $ecommerce->updateQuantity('AIRPODS-PRO', 1);

    // Aplicar cupom
    $ecommerce->applyCoupon('DESC10');

    // Adicionar frete
    $ecommerce->addShipping('01234-567');

    // Exibir carrinho final
    $ecommerce->displaySummary();

    echo "\n🔄 Opções avançadas:\n";
    echo str_repeat("-", 30) . "\n";

    // Exemplo de análise do carrinho
    $cart = $ecommerce->getCart();

    echo "📊 Estatísticas do carrinho:\n";
    echo "- Total de itens únicos: " . $cart->totalUniqueItems() . "\n";
    echo "- Total de itens: " . $cart->totalItems() . "\n";
    echo "- Valor médio por item: R$ " . number_format($cart->subtotal() / $cart->totalItems(), 2, ',', '.') . "\n";

    // Filtrar produtos por categoria
    $eletronicos = $cart->filter(fn($item) =>
    str_contains(strtolower($item->get('category') ?? ''), 'eletrônico')
    );
    echo "- Produtos eletrônicos: " . count($eletronicos) . "\n";

    // Produtos mais caros que R$ 500
    $produtosCaros = $cart->filter(fn($item) => $item->getPrice() > 500);
    echo "- Produtos > R$ 500: " . count($produtosCaros) . "\n";

    echo "\n💾 Salvando carrinho para mais tarde...\n";
    $savedId = $ecommerce->saveForLater();

    echo "\n🛒 Finalizando compra...\n";
    $orderData = $ecommerce->checkout();

    echo "\n📄 Dados do pedido gerado:\n";
    echo "ID do Carrinho: " . $orderData['cart_id'] . "\n";
    echo "Timestamp: " . $orderData['timestamp'] . "\n";
    echo "Itens no pedido: " . count($orderData['items']) . "\n";
}