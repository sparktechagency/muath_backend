<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
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

    public function createCheckout1(Request $request)
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

        // return [
        //     'total_amount' => $total_amount * 100,
        //     'lineitems' => $arr,
        // ];

        $payload = [
            "currency" => "USD",
            "amount" => $total_amount * 100, // cents ($7.50)
            "redirectUrl" => url('/payment/success'), // Redirect URL on success
            "shoppingCart" => [
                "lineItems" => $arr
            ],
            "customer" => [
                'full_name' => $request->full_name,
                "address" => $request->address,
                "phone_number" => $request->phone_number,
                "city" => $request->city,
                "zip_code" => $request->zip_code,
                "country" => $request->country,
            ]
        ];

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-Clover-Merchant-Id' => $this->merchantId,
        ])->post("{$this->baseUrl}/invoicingcheckoutservice/v1/checkouts", $payload);

        $data = $response->json();

        return response()->json([
            "error" => "Checkout creation successfully.",
            "response" => $data
        ], 400);
    }

    public function createCheckout2(Request $request)
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
            "amount" => $total_amount * 100, // cents ($7.50)
            "redirectUrl" => url('/payment/success'), // Redirect URL on success
            "shoppingCart" => [
                "lineItems" => $arr
            ],
            "customer" => [
                'full_name' => $request->full_name,
                "address" => [
                    "address_line_1" => $request->address_line_1,  // Assuming $request->address contains the street address
                    "address_line_2" => $request->address_line_2,                // Optional
                    "city" => $request->city,
                    "state" => $request->state,                         // Optional
                    "zip_code" => $request->zip_code,
                    "country" => $request->country,
                ],
                "phone_number" => $request->phone_number,
            ]
        ];

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-Clover-Merchant-Id' => $this->merchantId,
        ])->post("{$this->baseUrl}/invoicingcheckoutservice/v1/checkouts", $payload);

        $data = $response->json();

        return response()->json([
            "error" => "Checkout creation successfully.",
            "response" => $data
        ], 400);
    }


    public function createCheckout3(Request $request)
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

        // Address, country code and zip code validation
        $addressLine1 = $request->address_line_1 ?? '';  // Ensure address is not null
        $countryCode = strtoupper($request->country) ?? '';  // Ensure country is in 2-letter code format
        $zipCode = $request->zip_code ?? '';  // Ensure zip code is non-null

        if (!$addressLine1) {
            return response()->json(['error' => 'Address line 1 is required.'], 400);
        }

        if (strlen($countryCode) > 2) {
            return response()->json(['error' => 'Country code must be 2 letters.'], 400);
        }

        if (!$zipCode) {
            return response()->json(['error' => 'Zip code is required.'], 400);
        }

        $payload = [
            "currency" => "USD",
            "amount" => $total_amount * 100, // cents ($7.50)
            "redirectUrl" => url('/payment/success'), // Redirect URL on success
            "shoppingCart" => [
                "lineItems" => $arr
            ],
            "customer" => [
                'full_name' => $request->full_name,
                "address" => [
                    "address_line_1" => $addressLine1, // Ensure non-null address
                    "address_line_2" => $request->address_line_2,            // Optional
                    "city" => $request->city,
                    "state" => $request->state,                     // Optional
                    "zip_code" => $zipCode,            // Ensure non-null zip code
                    "country" => $countryCode,         // 2-letter country code
                ],
                "phone_number" => $request->phone_number,
            ]
        ];

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-Clover-Merchant-Id' => $this->merchantId,
        ])->post("{$this->baseUrl}/invoicingcheckoutservice/v1/checkouts", $payload);

        $data = $response->json();

        return response()->json([
            "error" => "Checkout creation successfully.",
            "response" => $data
        ], 400);
    }


    public function createCheckout4(Request $request)
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

        // Address, country code and zip code validation
        $addressLine1 = $request->address_line_1 ?? '';  // Ensure address is not null
        $countryCode = strtoupper($request->country) ?? '';  // Ensure country is in 2-letter code format
        $zipCode = $request->zip_code ?? '';  // Ensure zip code is non-null

        if (!$addressLine1) {
            return response()->json(['error' => 'Address line 1 is required.'], 400);
        }

        if (strlen($countryCode) != 2) {
            return response()->json(['error' => 'Country code must be 2 letters.'], 400);
        }

        if (!$zipCode) {
            return response()->json(['error' => 'Zip code is required.'], 400);
        }

        $payload = [
            "currency" => "USD",
            "amount" => $total_amount * 100, // cents ($7.50)
            "redirectUrl" => url('/payment/success'), // Redirect URL on success
            "shoppingCart" => [
                "lineItems" => $arr
            ],
            "customer" => [
                'full_name' => $request->full_name,
                "address" => [
                    "address_line_1" => $addressLine1, // Ensure non-null address
                    "address_line_2" => $request->address_line_2,            // Optional
                    "city" => $request->city,
                    "state" => $request->state,                     // Optional
                    "zip_code" => $zipCode,            // Ensure non-null zip code
                    "country" => $countryCode,         // 2-letter country code
                ],
                "phone_number" => $request->phone_number,
            ]
        ];

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-Clover-Merchant-Id' => $this->merchantId,
        ])->post("{$this->baseUrl}/invoicingcheckoutservice/v1/checkouts", $payload);

        $data = $response->json();

        return response()->json([
            "error" => "Checkout creation successfully.",
            "response" => $data
        ], 400);
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


    // Address, country code and zip code validation
    $addressLine1 = $request->address1;  // Ensure address is not null
    $countryCode = strtoupper($request->country);  // Ensure country is in 2-letter code format
    $zipCode = $request->zip;  // Ensure zip code is non-null

    

    $payload = [
        "currency" => "USD",
        "amount" => $total_amount * 100, // cents ($7.50)
        "redirectUrl" => url('/payment/success'), // Redirect URL on success
        "shoppingCart" => [
            "lineItems" => $arr
        ],
        "customer" => [
            'full_name' => $request->full_name,
            "address" => [
                "address1" => $addressLine1, // Ensure non-null address
                "address2" => $request->address2,            // Optional
                "city" => $request->city,
                "state" => $request->state,                     // Optional
                "zip" => $zipCode,            // Ensure non-null zip code
                "country" => $countryCode,         // 2-letter country code
            ],
            "phone_number" => $request->phone_number,
        ]
    ];

    $response = Http::withHeaders([
        'accept' => 'application/json',
        'content-type' => 'application/json',
        'Authorization' => 'Bearer ' . $this->apiKey,
        'X-Clover-Merchant-Id' => $this->merchantId,
    ])->post("{$this->baseUrl}/invoicingcheckoutservice/v1/checkouts", $payload);

    $data = $response->json();

    return response()->json([
        "error" => "Checkout creation successfully.",
        "response" => $data
    ], 400);
}


    public function paymentSuccess(Request $request)
    {
        $checkoutSessionId = $request->query('checkoutSessionId');

        if (!$checkoutSessionId) {
            return response()->json(["error" => "No checkoutSessionId found"]);
        }

        // Fetch payment details after redirect
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get("{$this->baseUrl}/invoicingcheckoutservice/v1/checkouts/{$checkoutSessionId}");

        $paymentDetails = $response->json();

        return response()->json([
            "message" => "Payment Successful",
            "checkoutSessionId" => $checkoutSessionId,
            "paymentDetails" => $paymentDetails
        ]);
    }

    public function paymentFail(Request $request)
    {
        return response()->json([
            "message" => "Payment Failed"
        ]);
    }
}







