<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_can_be_created(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Test Laptop',
            'sku' => 'TEST-LAP-001',
            'price' => 50000,
            'stock' => 10,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product created successfully.',
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Laptop',
            'sku' => 'TEST-LAP-001',
            'stock' => 10,
        ]);
    }

    public function test_stock_can_be_updated(): void
    {
        $product = Product::create([
            'name' => 'Test Mouse',
            'sku' => 'TEST-MOU-001',
            'price' => 1500,
            'stock' => 10,
        ]);

        $response = $this->postJson(
            "/api/products/{$product->id}/stock",
            [
                'quantity' => 5,
            ]
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Stock updated successfully.',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 15,
        ]);
    }
}