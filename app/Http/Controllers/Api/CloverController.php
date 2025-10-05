<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Metadata;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Pail\ValueObjects\Origin\Http as OriginHttp;

class CloverController extends Controller
{

    protected $apiKey;
    protected $merchantId;
    protected $baseUrl;

    public function __construct()
    {
        // Configuration values
        $this->apiKey = config('services.clover.api_key');
        $this->merchantId = config('services.clover.merchant_id');
        $this->baseUrl = config('services.clover.base_url', 'https://apisandbox.dev.clover.com');

    }

    public function createCheckout(Request $request)
    {
        $orders = is_string($request->orders) ? json_decode($request->orders, true) : $request->orders;

        if (!is_array($orders)) {
            return response()->json(['error' => 'Invalid orders format'], 400);
        }

        $arr = [];
        $total_amount = 0;

        foreach ($orders as $item) {
            $product_id = $item['product_id'];
            $unitQty = $item['unitQty'];

            $product = Product::where('id', $product_id)->first();

            $arr[] = [
                'note' => $product->description,
                'name' => $product->name,
                'price' => $product->price * 100,
                'unitQty' => $unitQty,
            ];

            $total_amount = $total_amount + $product->price;
        }

        $payload = [
            "currency" => "USD",
            "amount" => $total_amount, // cents ($7.50)
            "redirectUrl" => url('/payment/success'),
            "shoppingCart" => [
                "lineItems" => $arr
            ],
            "customer" => [
                "email" => Auth::user()->email,
                "firstName" => Auth::user()->full_name,
                "lastName" => ' ',
            ]

        ];

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-Clover-Merchant-Id' => $this->merchantId,
        ])->post("{$this->baseUrl}/invoicingcheckoutservice/v1/checkouts", $payload);

        $data = $response->json();

        if (isset($data['checkoutSessionId'])) {
            Metadata::create([
                'checkout_session_id' => $data['checkoutSessionId'],
                'user_id' => Auth::id(),
                'full_name' => $request->full_name,
                'address' => $request->address,
                'phone_number' => $request->phone_number,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                'country' => $request->country
            ]);
        }

        return response()->json([
            'status' => isset($data['checkoutSessionId']) ? true : false,
            "message" => isset($data['checkoutSessionId']) ? "Checkout creation successfully." : "You are not authorized to access this resource.",
            "response" => $data
        ], isset($data['checkoutSessionId']) ? 201 : 401);
    }


    public function paymentSuccess(Request $request)
    {
        $checkoutSessionId = $request->query('checkoutSessionId');

        if (!$checkoutSessionId) {
            return response()->json(["error" => "No checkoutSessionId found"]);
        }

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get("{$this->baseUrl}/invoicingcheckoutservice/v1/checkouts/{$checkoutSessionId}");

        $details = $response->json();

        $paymentStatus = $details['status'];

        if ($paymentStatus != 'PAID') {
            return response()->json([
                "status" => false,
                "message" => "Your payment status unpaid or expired",
            ]);
        }

        $lineItems = $details['shoppingCart']['lineItems'];

        $metadata = Metadata::where('checkout_session_id', $checkoutSessionId)->where('user_id', Auth::id())->first();


        $getTransation = Http::withHeaders([
            'accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get("{$this->baseUrl}/v3/merchants/{$details['merchant']['id']}/payments/{$details['paymentDetails'][0]['id']}");

        $is_checkout_session = Order::where('checkout_session_id', $checkoutSessionId)->exists();

        if (!$is_checkout_session) {
            $order = Order::create([
                'checkout_session_id' => $checkoutSessionId,
                'user_id' => $metadata->user_id,
                'order_id' => $getTransation['order']['id'],
                'price' => $details['paymentDetails'][0]['amount'] / 100,
            ]);

            foreach ($lineItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'name' => $item['name'],
                    'quantity' => $item['unitQty'],
                    'price' => $item['price'],
                ]);
            }

            Transaction::create([
                'checkout_session_id' => $checkoutSessionId,
                'user_id' => $metadata->user_id,
                'transaction_id' => $getTransation['id'],
                'amount' => $details['paymentDetails'][0]['amount'] / 100,
                'payment_date' => Carbon::now(),
                'status' => 'Completed',
            ]);

            return response()->json([
                "status" => true,
                "message" => "Payment Successfull.",
                "metadata" => $metadata,
                "delails" => $details,
            ]);
        } else {
            return response()->json([
                "status" => true,
                "message" => "Payment already stored.",
            ]);
        }
    }

}







