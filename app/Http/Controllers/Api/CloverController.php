<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Pail\ValueObjects\Origin\Http as OriginHttp;

class CloverController extends Controller
{

    public function createPayment(Request $request)
    {
        $merchantId = config('clover.merchant_id');
        $apiKey = config('clover.api_key');

        $url = "https://api.clover.com/v3/merchants/{$merchantId}/payments";

        // Prepare payment data
        $paymentData = [
            'amount' => $request->amount,  // Payment amount in cents
            'currency' => 'USD',
        ];

        // Make the POST request to Clover API
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, $paymentData);


            // return $response;

            if ($response->successful()) {
                // Handle the successful response
                return $response->json();
            } else {
                // Handle the error
                return response()->json([
                    'status' => 'error',
                    'message' => $response->json()['error']['message'] ?? 'Unknown error',
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}




