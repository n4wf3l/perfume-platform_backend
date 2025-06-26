<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        return Product::with(['images', 'category'])->get();
    }

    public function show($id)
    {
        return Product::with(['images', 'category'])->findOrFail($id);
    }

   public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'stock' => 'required|integer',
                'size_ml' => 'required|integer',
                'category_id' => 'required|exists:categories,id',
                'is_hero' => 'nullable|boolean',
                'is_flagship' => 'nullable|boolean',
                'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
            ]);

            // Limit flagship count
            if ($request->boolean('is_flagship') && Product::where('is_flagship', true)->count() >= 3) {
                throw ValidationException::withMessages([
                    'is_flagship' => 'Maximum 3 flagship products allowed.',
                ]);
            }

            // Make hero unique
            if ($request->boolean('is_hero')) {
                Product::where('is_hero', true)->update(['is_hero' => false]);
            }

            $product = Product::create($validated);

            // Handle images (max 3)
            if ($request->hasFile('images')) {
                $images = $request->file('images');

                if (count($images) > 3) {
                    throw ValidationException::withMessages([
                        'images' => 'You can upload a maximum of 3 images per product.',
                    ]);
                }

                foreach ($images as $index => $image) {
                    $path = $image->store('products', 'public');

                    if (!$path) {
                        throw new \Exception("Failed to store image at index $index");
                    }

                    $product->images()->create([
                        'path' => $path,
                        'order' => $index,
                    ]);
                }
            }

            Log::info('✅ Product created', ['id' => $product->id]);

            return response()->json($product->load('category', 'images'), 201);
        } catch (ValidationException $e) {
            Log::warning('⚠️ Validation failed', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            Log::error('❌ Product creation failed', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

public function update(Request $request, $id): JsonResponse
{
    try {
        Log::info("🔄 Starting update for product ID: $id");

        $product = Product::findOrFail($id);
        Log::info("✅ Product found", ['product' => $product]);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'stock' => 'sometimes|integer',
            'size_ml' => 'sometimes|integer',
            'category_id' => 'sometimes|exists:categories,id',
            'is_hero' => 'nullable|boolean',
            'is_flagship' => 'nullable|boolean',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        Log::info("✅ Validation passed", ['validated' => $validated]);

        // Ensure only 1 hero product
        if ($request->boolean('is_hero')) {
            Product::where('is_hero', true)
                ->where('id', '!=', $product->id)
                ->update(['is_hero' => false]);
            $validated['is_hero'] = true;
        }

        // Ensure max 3 flagship products
        if ($request->boolean('is_flagship')) {
            $count = Product::where('is_flagship', true)
                ->where('id', '!=', $product->id)
                ->count();

            Log::info("🟡 Flagship count excluding this: $count");

            if ($count >= 3) {
                throw ValidationException::withMessages([
                    'is_flagship' => 'Maximum 3 flagship products allowed.',
                ]);
            }

            $validated['is_flagship'] = true;
        }

        $product->update($validated);
        Log::info("✅ Product updated", ['product_id' => $product->id]);

        // Handle image replacement
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            Log::info("📷 Images received", ['count' => count($images)]);

            if (count($images) > 3) {
                throw ValidationException::withMessages([
                    'images' => 'You can upload a maximum of 3 images.',
                ]);
            }

            // Delete old images
            foreach ($product->images as $image) {
                Log::info("🗑️ Deleting image", ['path' => $image->path]);

                if (Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }
                $image->delete();
            }

            // Store new images
            foreach ($images as $index => $image) {
                $path = $image->store('products', 'public');

                if (!$path) {
                    throw new \Exception("Failed to store image at index $index");
                }

                $product->images()->create([
                    'path' => $path,
                    'order' => $index,
                ]);

                Log::info("✅ Image stored", ['index' => $index, 'path' => $path]);
            }
        } else {
            Log::warning("⚠️ No images received in update request");
        }

        return response()->json($product->load('category', 'images'));

    } catch (ValidationException $e) {
        Log::warning("⚠️ Validation failed", ['errors' => $e->errors()]);
        return response()->json(['errors' => $e->errors()], 422);

    } catch (\Throwable $e) {
        Log::error("❌ Update failed", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Something went wrong during update.',
            'details' => $e->getMessage()
        ], 500);
    }
}


    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }
}
