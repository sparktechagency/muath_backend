<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pack;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function addProduct(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'description' => 'required|string',
                'images' => 'required|array|max:5',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:20480',
                'packs' => 'nullable',
                'additional_description' => 'nullable|string',
                'is_offer' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 422);
            }

            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePaths[] = '/storage/' . $image->store('products', 'public');
                }
            }

            $product = Product::create([
                'category' => $request->category,
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description,
                'images' => json_encode($imagePaths),
                'additional_description' => $request->additional_description,
                'is_offer' => $request->is_offer,

            ]);

            $packs = is_string($request->packs) ? json_decode($request->packs, true) : $request->packs;
            if (!is_array($packs)) {
                return response()->json(['error' => 'Invalid packs format'], 400);
            }

            $pack_arr = [];
            foreach ($packs as $item) {
                $packs = Pack::create([
                    'product_id' => $product->id,
                    'pack_size' => $item['pack_size'],
                    'price' => $item['price']
                ]);

                $pack_arr[] = $packs;
            }

            $product->packs = $pack_arr;

            return $this->sendResponse($product, 'Product added successfully.', true, 201);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    public function getProducts(Request $request)
    {
        try {
            $query = Product::with('packs'); // Correct the query initialization

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('description', 'like', "%$search%");
                });
            }

            if ($request->has('filter') && !empty($request->filter)) {
                $filter = $request->filter;
                $query->where('category', $filter);
            }

            $products = $query->latest()->paginate(10);

            // Process product images and packs
            foreach ($products as $product) {
                $product->images = array_map(function ($image) {
                    return asset($image);
                }, json_decode($product->images, true));

                $product->packs = !empty($product->packs) ? json_decode($product->packs, true) : [];
            }

            return $this->sendResponse($products, 'Get products.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    public function editProduct(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category' => 'nullable|string|max:255',
                'name' => 'nullable|string|max:255',
                'price' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:20480', // 20 MB
                'packs' => 'nullable',
                'additional_description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 422);
            }

            $product = Product::findOrFail($id);

            $oldImages = json_decode($product->images, true);

            if ($request->hasFile('images')) {
                foreach ($oldImages as $image) {
                    $imagePath = public_path($image);
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }

                $imagePaths = [];
                foreach ($request->file('images') as $image) {
                    $imagePaths[] = '/storage/' . $image->store('products', 'public');
                }
                $product->images = json_encode($imagePaths) ?? $product->images;
            }

            $product->category = $request->category ?? $product->category;
            $product->name = $request->name ?? $product->name;
            $product->price = $request->price ?? $product->price;
            $product->description = $request->description ?? $product->description;
            $product->additional_description = $request->additional_description ?? $product->additional_description;

            $product->save();

            if ($request->has('packs')) {
                $product->packs()->delete();

                $packs = is_string($request->packs) ? json_decode($request->packs, true) : $request->packs;

                if (!is_array($packs)) {
                    return response()->json(['error' => 'Invalid packs format'], 400);
                }

                $pack_arr = [];
                foreach ($packs as $item) {
                    $packs = Pack::create([
                        'product_id' => $product->id,
                        'pack_size' => $item['pack_size'],
                        'price' => $item['price']
                    ]);
                    $pack_arr[] = $packs;
                }
            }

            $product->packs = $pack_arr;

            return $this->sendResponse($product, 'Product updated successfully.', true, 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    public function viewProduct($id)
    {
        try {
            $product = Product::with('packs')->find($id);

            if (!$product) {
                return $this->sendError('Product not found!', []);
            }

            $product->images = array_map(function ($image) {
                return asset($image);
            }, json_decode($product->images, true));

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

