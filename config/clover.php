<?php

return [
    // Merchant ID from Clover Developer Portal
    'merchant_id' => env('CLOVER_MERCHANT_ID'),

    // API Key from Clover Developer Portal
    'api_key' => env('CLOVER_API_KEY'),

    // Base URL for Clover API (can be sandbox or production)
    'api_url' => 'https://api.clover.com/v3/merchants/',

    // Optional: You can add more config options if needed
];
