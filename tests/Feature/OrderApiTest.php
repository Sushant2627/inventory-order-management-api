<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_be_created_and_stock_is_deducted(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);

        $product = Product::create([
            'name' => 'Test Laptop',
            'sku' => 'ORDER-LAP-001',
            'price' => 50000,
            'stock' => 10,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Order created successfully.',
            ]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'total_amount' => 100000,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 100000,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 8,
        ]);
    }

    public function test_order_cannot_be_created_when_stock_is_insufficient(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'stock@example.com',
        ]);

        $product = Product::create([
            'name' => 'Limited Laptop',
            'sku' => 'ORDER-LAP-002',
            'price' => 50000,
            'stock' => 2,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient stock for product: Limited Laptop',
            ]);

        $this->assertDatabaseMissing('orders', [
            'customer_id' => $customer->id,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 2,
        ]);
    }

    public function test_order_can_be_cancelled_and_stock_is_restored(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'cancel@example.com',
        ]);

        $product = Product::create([
            'name' => 'Cancel Laptop',
            'sku' => 'ORDER-LAP-003',
            'price' => 50000,
            'stock' => 10,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'total_amount' => 100000,
            'status' => 'completed',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 50000,
            'subtotal' => 100000,
        ]);

        $product->decrement('stock', 2);

        $response = $this->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order cancelled successfully and stock restored.',
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }

    public function test_cancelled_order_cannot_be_cancelled_again(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'double-cancel@example.com',
        ]);

        $product = Product::create([
            'name' => 'Double Cancel Laptop',
            'sku' => 'ORDER-LAP-004',
            'price' => 50000,
            'stock' => 10,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'total_amount' => 50000,
            'status' => 'cancelled',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 50000,
            'subtotal' => 50000,
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Order is already cancelled.',
            ]);
    }
}