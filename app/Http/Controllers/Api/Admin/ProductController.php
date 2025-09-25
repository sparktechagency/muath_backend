<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function addProduct(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'description' => 'required|string',
                'images' => 'required|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:20480', // 20 MB
            ]);

            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePaths[] = '/storage/' . $image->store('products', 'public');
                }
            }

            $product = Product::create([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'description' => $validated['description'],
                'images' => json_encode($imagePaths),
            ]);

            return response()->json(['status' => true, 'data' => $product], 201);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 404);
        }
    }
    public function getProducts(Request $request)
    {
        try {
            $query = Product::query();

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            }

            $products = $query->latest()->paginate(10);

            foreach ($products as $product) {
                $product->images = json_decode($product->images);
            }

            return $this->sendResponse($products, 'Get products.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    public function editProduct(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'price' => 'sometimes|numeric|min:0',
                'description' => 'sometimes|string',
                'images' => 'sometimes|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:20480', // 20 MB
            ]);

            $product = Product::find($id);

            if (!$product) {
                return $this->sendError('Product not found!.', []);
            }

            if ($request->hasFile('images')) {
                if ($product->images) {
                    $oldImages = json_decode($product->images, true);
                    foreach ($oldImages as $oldImage) {
                        $oldImagePath = str_replace('/storage/', '', $oldImage);
                        if (Storage::disk('public')->exists($oldImagePath)) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                    }
                }

                $imagePaths = [];
                foreach ($request->file('images') as $image) {
                    $imagePaths[] = '/storage/' . $image->store('products', 'public');
                }
                $validated['images'] = json_encode($imagePaths);
            }

            $product->update($validated);
            return $this->sendResponse($product, 'Product updated successfully.');

        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    public function viewProduct($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return $this->sendError('Product not found!.', []);
            }
            $product->images = json_decode($product->images);
            return $this->sendResponse($product, 'View product.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    public function deleteProduct($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return $this->sendError('Product not found!.', []);
            }
            $product->delete();
            return $this->sendResponse($product, 'Product deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}





