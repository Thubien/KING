<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shopify API Configuration
    |--------------------------------------------------------------------------
    */
    
    'client_id' => env('SHOPIFY_CLIENT_ID'),
    'client_secret' => env('SHOPIFY_CLIENT_SECRET'),
    'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),
    
    /*
    |--------------------------------------------------------------------------
    | API Version & Scopes
    |--------------------------------------------------------------------------
    */
    
    'api_version' => '2024-01',
    
    'scopes' => [
        'read_orders',
        'read_products', 
        'read_analytics',
        'read_customers',
        'read_reports'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | OAuth URLs
    |--------------------------------------------------------------------------
    */
    
    'oauth_authorize_url' => 'https://{shop}.myshopify.com/admin/oauth/authorize',
    'oauth_access_token_url' => 'https://{shop}.myshopify.com/admin/oauth/access_token',
    
    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    */
    
    'connection_timeout' => 30, // seconds
    'max_retries' => 3,
    
    /*
    |--------------------------------------------------------------------------
    | Store Limits by Plan
    |--------------------------------------------------------------------------
    */
    
    'store_limits' => [
        'starter' => 3,
        'professional' => 10,
        'enterprise' => 999
    ],
];