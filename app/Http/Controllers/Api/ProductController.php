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
    public function index(Request $request)
{
    $query = Product::with(['images', 'category']);

    if ($request->has('is_hero')) {
        $query->where('is_hero', true);
    }

    if ($request->has('is_flagship')) {
        $query->where('is_flagship', true);
    }

    return $query->get();
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
                'olfactive_notes' => 'nullable|string',
                'gender' => 'nullable|string|in:male,female,unisex',
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
            'olfactive_notes' => 'nullable|string',
            'gender' => 'nullable|string|in:male,female,unisex',
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

    } catch (Throwable $e) {
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

   public function updateImageOrder(Request $request, $productId): JsonResponse
{
    try {
        $product = Product::findOrFail($productId);

        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:product_images,id', // Fix: changed from images to product_images
            'orders.*.order' => 'required|integer|min:0|max:2',
        ]);

        foreach ($request->orders as $imgOrder) {
            $image = $product->images()->findOrFail($imgOrder['id']);
            $image->update(['order' => $imgOrder['order']]);
        }

        return response()->json($product->images()->orderBy('order')->get());
    } catch (Throwable $e) {
        Log::error("❌ Image reorder failed", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Failed to update image order',
            'details' => $e->getMessage()
        ], 500);
    }
}

public function updateImage(Request $request, $productId, $imageId): JsonResponse
{
    $product = Product::findOrFail($productId);
    $image = $product->images()->findOrFail($imageId);

    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
    ]);

    // Delete old image file
    if (Storage::disk('public')->exists($image->path)) {
        Storage::disk('public')->delete($image->path);
    }

    // Store new image
    $path = $request->file('image')->store('products', 'public');
    $image->update(['path' => $path]);

    return response()->json($image->fresh());
}

public function updateSpecialProducts(Request $request): JsonResponse
{
    try {
        $validated = $request->validate([
            'featured' => 'required|array',
            'featured.*' => 'integer|exists:products,id',
            'hero' => 'required|array',
            'hero.*' => 'integer|exists:products,id',
        ]);

        Log::info("🔄 Updating special products", [
            'featured_count' => count($validated['featured']),
            'hero_count' => count($validated['hero'])
        ]);

        // Validate counts
        if (count($validated['featured']) > 3) {
            throw ValidationException::withMessages([
                'featured' => 'Maximum 3 featured products allowed.',
            ]);
        }

        if (count($validated['hero']) > 1) {
            throw ValidationException::withMessages([
                'hero' => 'Only 1 hero product allowed.',
            ]);
        }

        // Reset all special statuses
        Product::query()->update([
            'is_flagship' => false,
            'is_hero' => false,
        ]);

        // Set featured products
        if (!empty($validated['featured'])) {
            Product::whereIn('id', $validated['featured'])->update(['is_flagship' => true]);
        }

        // Set hero product
        if (!empty($validated['hero'])) {
            Product::whereIn('id', $validated['hero'])->update(['is_hero' => true]);
        }

        return response()->json([
            'message' => 'Special products updated successfully',
            'featured_count' => count($validated['featured']),
            'hero_count' => count($validated['hero']),
        ]);
    } catch (ValidationException $e) {
        Log::warning('⚠️ Validation failed', ['errors' => $e->errors()]);
        return response()->json(['errors' => $e->errors()], 422);
    } catch (Throwable $e) {
        Log::error('❌ Special products update failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Failed to update special products',
            'details' => $e->getMessage()
        ], 500);
    }
}
}
