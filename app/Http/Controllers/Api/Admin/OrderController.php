<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Metadata;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function getOrders(Request $request)
    {
        $search = $request->input('search', ''); // Default to empty if not provided
        $status = $request->input('status', ''); // Get status filter (e.g., Pending, Completed)

        // Query for orders, filtering based on search term, user name and status filter
        $orders = Order::with([
            'user' => function ($q) {
                $q->select('id', 'full_name', 'role', 'avatar');
            }
        ])  // Ensure user relationship is loaded
            ->when($search, function ($query) use ($search) {
                return $query->where('order_id', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('full_name', 'like', "%{$search}%");
                    });
            })
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->get();

        foreach ($orders as $order) {
            $order->metadata = Metadata::where('checkout_session_id', $order->checkout_session_id)->first();
        }

        return response()->json([
            'status' => true,
            'message' => 'Get orders',
            'orders' => $orders
        ]);
    }

    public function viewOrder($id)
    {
        $order = Order::where('id', $id)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order->metadata = Metadata::where('checkout_session_id', $order->checkout_session_id)->first();

        return response()->json([
            'status' => true,
            'message' => 'View order',
            'order' => $order
        ]);
    }

    public function orderStatusChange(Request $request)
    {
        $request->validate([
            'status' => 'required|string|in:Pending,Completed'
        ]);

        $order = Order::where('id', $request->order_id)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'status' => true,
            'message' => 'Order status updated successfully',
            'order' => $order
        ]);
    }

}
