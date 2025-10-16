<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SendCustomOrder;
use App\Models\Metadata;
use App\Models\Order;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function getOrders(Request $request)
    {
        $search = $request->input('search', '');
        $status = $request->input('status', '');

        $orders = Order::with('metadata')
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

        return response()->json([
            'status' => true,
            'message' => 'Get orders',
            'orders' => $orders
        ]);
    }
    public function viewOrder($id)
    {
        $order = Order::with(['order_items', 'metadata'])->where('id', $id)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

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

    public function sendCustomOrder(Request $request)
    {
        $order = $request->all();

        if (isset($order['date'])) {
            $order['date'] = Carbon::createFromFormat('Y/m/d', $order['date'])->toDateString();
        }

        if (isset($order['date'])) {
            $order['date'] = Carbon::parse($order['date'])->format('d F, Y');
        }

        Mail::to('shifatghi@gmail.com')->send(new SendCustomOrder($order));

        return response()->json([
            'status' => true,
            'message' => 'Send order successfully.',
            'data' => $order,
        ], 201);

    }

}
