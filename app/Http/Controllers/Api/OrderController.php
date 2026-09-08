<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(): JsonResponse
    {
        $orders = Order::with(['customer', 'items.product'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Store a newly created order.
     */
    public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'customer_id' => [
            'required',
            'integer',
            'exists:customers,id',
        ],

        'products' => [
            'required',
            'array',
            'min:1',
        ],

        'products.*.product_id' => [
            'required',
            'integer',
            'exists:products,id',
        ],

        'products.*.quantity' => [
            'required',
            'integer',
            'min:1',
        ],
    ]);

    try {
        $order = DB::transaction(function () use ($validated) {

            $customer = Customer::findOrFail($validated['customer_id']);

            $totalAmount = 0;
            $orderItems = [];

            foreach ($validated['products'] as $item) {

                $product = Product::where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->stock < $item['quantity']) {
                    throw new \RuntimeException(
                        "Insufficient stock for product: {$product->name}"
                    );
                }

                $price = (float) $product->price;
                $quantity = (int) $item['quantity'];
                $subtotal = $price * $quantity;

                $totalAmount += $subtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ];

                $product->decrement('stock', $quantity);
            }

            $order = Order::create([
                'customer_id' => $customer->id,
                'total_amount' => $totalAmount,
                'status' => 'completed',
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            return $order;
        });

        $order->load(['customer', 'items.product']);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => $order,
        ], 201);

    } catch (\RuntimeException $e) {

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}

    /**
     * Display the specified order.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['customer', 'items.product']);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Cancel an order and return stock.
     */
   public function cancel(Order $order): JsonResponse
{
    try {
        DB::transaction(function () use ($order) {

            $order = Order::where('id', $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status === 'cancelled') {
                throw new \RuntimeException('Order is already cancelled.');
            }

            $order->load('items');

            foreach ($order->items as $item) {

                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $product->increment('stock', $item->quantity);
            }

            $order->update([
                'status' => 'cancelled',
            ]);
        });

        $order->refresh();
        $order->load(['customer', 'items.product']);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully and stock restored.',
            'data' => $order,
        ]);

    } catch (\RuntimeException $e) {

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}
}