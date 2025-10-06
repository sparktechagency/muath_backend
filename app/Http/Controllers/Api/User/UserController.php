<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Metadata;
use App\Models\Order;
use App\Models\Product;
use App\Models\Report;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getProducts(Request $request)
    {
        try {
            $query = Product::query();

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            }

            if ($request->has('filter') && !empty($request->filter)) {
                $filter = $request->filter;
                $query->where('category', $filter);
            }

            $products = $query->latest()->paginate(10);

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
    public function viewProduct($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return $this->sendError('Product not found!.', []);
            }
            $product->images = array_map(function ($image) {
                return asset($image);
            }, json_decode($product->images, true));
            $product->packs = !empty($product->packs) ? json_decode($product->packs, true) : [];


            $product->related = Product::where('category', $product->category)
                ->where('id', '!=', $product->id)
                ->get();

            foreach ($product->related as $relatedProduct) {
                $relatedProduct->images = json_decode($relatedProduct->images);
            }

            return $this->sendResponse($product, 'View product.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $existingCartItem = Cart::where('user_id', Auth::id())
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($existingCartItem) {
            $existingCartItem->quantity += $validated['quantity'];
            $existingCartItem->save();
        } else {
            Cart::create([
                'user_id' => Auth::id(),
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
            ]);
        }
        return response()->json(['status' => true, 'message' => 'Product added to cart']);
    }
    public function myCart()
    {
        $cartItems = Cart::where('user_id', Auth::id())
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Your cart is empty']);
        }

        return response()->json(['status' => true, 'data' => $cartItems]);
    }
    public function clearMyCart()
    {
        Cart::where('user_id', Auth::id())->delete();
        return response()->json(['status' => true, 'message' => 'Your cart has been cleared']);
    }
    public function removeCartProduct($id)
    {
        Cart::where('id', $id)->delete();
        return response()->json(['status' => true, 'message' => 'Product removed from cart.']);
    }
    public function sendFeedback(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'feedback' => 'required|string|max:255'
        ]);

        $report = Report::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'feedback' => $validated['feedback'],
        ]);

        return response()->json(['status' => true, 'message' => 'Send feedback to admin.', 'data' => $report]);
    }
    public function countUp(Request $request)
    {
        $cart_item = Cart::where('id', $request->cart_id)->first();

        if ($cart_item) {
            if ($cart_item->quantity < 10) {
                $cart_item->increment('quantity', 1);
                $cart_item->save();
            }
        }
        return response()->json($cart_item);
    }
    public function countDown(Request $request)
    {
        $cart_item = Cart::where('id', $request->cart_id)->first();

        if ($cart_item) {
            if ($cart_item->quantity > 1) {
                $cart_item->decrement('quantity', 1);
                $cart_item->save();
            }
        }
        return response()->json($cart_item);
    }
    public function getMyOrders(Request $request)
    {
        $orders = Order::with(['user','order_items'])->where('user_id',Auth::id())->latest()->get();
        return response()->json([
            'status' => true,
            'message' => 'Get my orders',
            'orders' => $orders
        ]);
    }

}
