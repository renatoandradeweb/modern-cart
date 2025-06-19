<?php
// examples/api-integration.php
// Exemplo de integração com API REST

/**
 * Classe para integração com APIs de e-commerce
 */
class CartApiIntegration
{
    private Cart $cart;

    public function __construct(string $cartId, string $storageType = 'session')
    {
        $store = match($storageType) {
            'cookie' => new \ModernCart\Storage\CookieStore(),
            'file' => new \ModernCart\Storage\FileStore(sys_get_temp_dir() . '/carts'),
            'memory' => new \ModernCart\Storage\MemoryStore(),
            default => new \ModernCart\Storage\SessionStore()
        };

        $this->cart = new Cart($cartId, $store);
    }

    /**
     * Endpoint: GET /api/cart
     */
    public function getCart(): array
    {
        return [
            'success' => true,
            'data' => $this->cart->toArray(),
            'summary' => $this->cart->summary()
        ];
    }

    /**
     * Endpoint: POST /api/cart/items
     */
    public function addItem(array $data): array
    {
        try {
            $item = new CartItem($data);
            $this->cart->add($item)->save();

            return [
                'success' => true,
                'message' => 'Item adicionado com sucesso',
                'item_id' => $item->getId(),
                'cart_summary' => $this->cart->summary()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Endpoint: PUT /api/cart/items/{itemId}
     */
    public function updateItem(string $itemId, array $data): array
    {
        try {
            foreach ($data as $key => $value) {
                $this->cart->update($itemId, $key, $value);
            }

            $this->cart->save();

            return [
                'success' => true,
                'message' => 'Item atualizado com sucesso',
                'cart_summary' => $this->cart->summary()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Endpoint: DELETE /api/cart/items/{itemId}
     */
    public function removeItem(string $itemId): array
    {
        try {
            $this->cart->remove($itemId)->save();

            return [
                'success' => true,
                'message' => 'Item removido com sucesso',
                'cart_summary' => $this->cart->summary()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Endpoint: DELETE /api/cart
     */
    public function clearCart(): array
    {
        $this->cart->clear();

        return [
            'success' => true,
            'message' => 'Carrinho limpo com sucesso'
        ];
    }

    /**
     * Endpoint: POST /api/cart/merge
     */
    public function mergeCart(string $otherCartId): array
    {
        try {
            $otherCart = new Cart($otherCartId, $this->cart->getStore());
            $this->cart->merge($otherCart)->save();

            return [
                'success' => true,
                'message' => 'Carrinhos mesclados com sucesso',
                'cart_summary' => $this->cart->summary()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Exemplo de uso da API
if (isset($_GET['api_example'])) {
    header('Content-Type: application/json');

    $api = new CartApiIntegration('api_cart_123');

    // Simular requisição POST para adicionar item
    $response = $api->addItem([
        'name' => 'Produto API',
        'price' => 99.99,
        'quantity' => 1
    ]);

    echo json_encode($response, JSON_PRETTY_PRINT);
}