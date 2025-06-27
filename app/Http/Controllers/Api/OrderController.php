<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Throwable;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'postal_code' => 'required|string|max:20',
                'paypal_order_id' => 'nullable|string|max:255',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            // Start calculating total
            $total = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
               $product = Product::findOrFail($item['product_id']);
                $price = $product->price;
                $quantity = $item['quantity'];

                // Optional: Check if enough stock is available
                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ["Product '{$product->name}' does not have enough stock."]
                    ]);
                }

                // Decrement stock
                $product->decrement('stock', $quantity);

                $total += $price * $quantity;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                ];

            }

            // Create the order
            $order = Order::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'postal_code' => $validated['postal_code'],
                'paypal_order_id' => $validated['paypal_order_id'] ?? null,
                'total' => $total,
                'status' => 'pending', // Or 'paid' depending on payment integration
            ]);

            // Create order items
            foreach ($itemsData as $item) {
                $item['order_id'] = $order->id;
                OrderItem::create($item);
            }

            Log::info('✅ Order created', ['order_id' => $order->id]);

            return response()->json($order->load('items.product'), 201);

        } catch (ValidationException $e) {
            Log::warning('⚠️ Order validation failed', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            Log::error('❌ Order creation failed', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Something went wrong while creating the order.'], 500);
        }
    }

    // Optional: List all orders (admin use)
    public function index()
    {
        return Order::with('items.product')->latest()->get();
    }

    // Optional: Show single order (admin use)
    public function show($id)
    {
        return Order::with('items.product')->findOrFail($id);
    }

    public function updateStatus(Request $request, Order $order)
{
    $request->validate([
        'status' => 'required|in:pending,paid,shipped,completed,cancelled'
    ]);

    $order->update(['status' => $request->status]);

    return response()->json($order);
}

public function destroy(Order $order)
{
    $order->delete();

    return response()->json(['message' => 'Order deleted successfully.'], 200);
}


}
