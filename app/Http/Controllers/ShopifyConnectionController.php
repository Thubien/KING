<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ShopifyConnectionController extends Controller
{
    public function connect(Request $request)
    {
        $request->validate([
            'shop_domain' => 'required|string'
        ]);

        $user = auth()->user();
        $company = $user->company;
        
        // Check subscription limits
        $connectedStores = $company->stores()->count();
        $maxStores = config('shopify.store_limits.' . $company->plan, 3);
        
        if ($connectedStores >= $maxStores) {
            return back()->withErrors([
                'limit' => "Store limit reached! Your {$company->plan} plan allows {$maxStores} stores. Upgrade to connect more stores."
            ]);
        }
        
        $shopDomain = $this->validateShopDomain($request->shop_domain);
        
        // Check if store already connected to any company
        if (Store::where('shopify_domain', $shopDomain)->exists()) {
            return back()->withErrors([
                'duplicate' => 'This Shopify store is already connected to EcomBoard.'
            ]);
        }
        
        // Build Shopify OAuth URL with state for security
        $state = encrypt([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'shop_domain' => $shopDomain,
            'timestamp' => now()->timestamp,
            'nonce' => \Str::random(32)
        ]);
        
        $authUrl = "https://{$shopDomain}/admin/oauth/authorize?" . http_build_query([
            'client_id' => config('shopify.client_id'),
            'scope' => implode(',', config('shopify.scopes')),
            'redirect_uri' => route('shopify.callback'),
            'state' => $state
        ]);
        
        Log::info('Shopify OAuth redirect initiated', [
            'shop_domain' => $shopDomain,
            'company_id' => $company->id,
            'user_id' => $user->id
        ]);
        
        return redirect($authUrl);
    }
    
    public function callback(Request $request)
    {
        // Validate required parameters
        if (!$request->has(['code', 'state', 'shop'])) {
            Log::error('Shopify callback missing required parameters', $request->all());
            return redirect()->route('filament.admin.pages.dashboard')
                ->withErrors(['callback' => 'Invalid Shopify callback. Missing required parameters.']);
        }
        
        // Validate and decrypt state parameter
        try {
            $stateData = decrypt($request->state);
        } catch (\Exception $e) {
            Log::error('Shopify callback invalid state parameter', ['error' => $e->getMessage()]);
            return redirect()->route('filament.admin.pages.dashboard')
                ->withErrors(['security' => 'Invalid connection request. Security validation failed.']);
        }
        
        // Check timestamp (prevent replay attacks - 5 minute window)
        if (now()->timestamp - $stateData['timestamp'] > 300) {
            Log::error('Shopify callback expired state', ['timestamp' => $stateData['timestamp']]);
            return redirect()->route('filament.admin.pages.dashboard')
                ->withErrors(['expired' => 'Connection request expired. Please try again.']);
        }
        
        $company = Company::findOrFail($stateData['company_id']);
        $shopDomain = $stateData['shop_domain'];
        
        // Verify shop domain matches
        if ($request->shop !== $shopDomain) {
            Log::error('Shopify callback domain mismatch', [
                'expected' => $shopDomain,
                'received' => $request->shop
            ]);
            return redirect()->route('filament.admin.pages.dashboard')
                ->withErrors(['mismatch' => 'Store domain mismatch. Security check failed.']);
        }
        
        // Exchange code for access token
        try {
            $tokenResponse = Http::timeout(30)->post("https://{$shopDomain}/admin/oauth/access_token", [
                'client_id' => config('shopify.client_id'),
                'client_secret' => config('shopify.client_secret'),
                'code' => $request->code
            ]);
            
            if (!$tokenResponse->successful()) {
                throw new \Exception('Shopify token exchange failed: ' . $tokenResponse->body());
            }
            
            $accessToken = $tokenResponse->json()['access_token'];
            
        } catch (\Exception $e) {
            Log::error('Shopify token exchange failed', [
                'shop_domain' => $shopDomain,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('filament.admin.pages.dashboard')
                ->withErrors(['token' => 'Failed to connect to Shopify. Please try again.']);
        }
        
        // Get store information from Shopify
        try {
            $storeInfo = $this->getStoreInfo($shopDomain, $accessToken);
        } catch (\Exception $e) {
            Log::error('Failed to get Shopify store info', [
                'shop_domain' => $shopDomain,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('filament.admin.pages.dashboard')
                ->withErrors(['info' => 'Failed to retrieve store information. Please try again.']);
        }
        
        // Create store record
        $store = Store::create([
            'company_id' => $company->id,
            'name' => $storeInfo['name'],
            'shopify_domain' => $shopDomain,
            'shopify_access_token' => $accessToken, // Will be encrypted by model
            'shopify_shop_id' => $storeInfo['id'],
            'currency' => $storeInfo['currency'],
            'country_code' => $storeInfo['country_code'] ?? null,
            'timezone' => $storeInfo['timezone'] ?? 'UTC',
            'status' => 'active',
            'last_sync_at' => now(),
            'sales_channel' => 'shopify',
            'data_source' => 'shopify_api'
        ]);
        
        Log::info('Shopify store connected successfully', [
            'store_id' => $store->id,
            'shop_domain' => $shopDomain,
            'company_id' => $company->id
        ]);
        
        // Trigger initial data sync
        \App\Jobs\SyncShopifyStoreData::dispatch($store);
        
        return redirect()->route('filament.admin.resources.stores.view', $store)
            ->with('success', "🎉 Successfully connected {$storeInfo['name']}! You can now set up partnerships and start managing transactions.");
    }
    
    public function disconnect(Store $store)
    {
        $user = auth()->user();
        
        // Check permissions
        if (!$user->isCompanyOwner() && !$user->isAdmin()) {
            return back()->withErrors(['permission' => 'Only company owners can disconnect stores.']);
        }
        
        // Check if store belongs to user's company
        if ($store->company_id !== $user->company_id) {
            return back()->withErrors(['access' => 'You can only disconnect stores from your company.']);
        }
        
        $storeName = $store->name;
        
        // Soft delete the store (preserves transaction history)
        $store->update([
            'status' => 'disconnected',
            'shopify_access_token' => null
        ]);
        
        Log::info('Shopify store disconnected', [
            'store_id' => $store->id,
            'company_id' => $user->company_id,
            'user_id' => $user->id
        ]);
        
        return back()->with('success', "Store '{$storeName}' has been disconnected. Transaction history is preserved.");
    }
    
    private function validateShopDomain(string $domain): string
    {
        // Remove protocol and trailing slashes
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = rtrim($domain, '/');
        
        // Add .myshopify.com if not present
        if (!str_ends_with($domain, '.myshopify.com')) {
            $domain .= '.myshopify.com';
        }
        
        // Validate format
        if (!preg_match('/^[a-zA-Z0-9\-]+\.myshopify\.com$/', $domain)) {
            throw ValidationException::withMessages([
                'shop_domain' => 'Invalid Shopify store URL format. Expected: your-store.myshopify.com'
            ]);
        }
        
        return $domain;
    }
    
    private function getStoreInfo(string $shopDomain, string $accessToken): array
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken
        ])->timeout(30)->get("https://{$shopDomain}/admin/api/" . config('shopify.api_version') . "/shop.json");
        
        if (!$response->successful()) {
            throw new \Exception("Failed to get store information: HTTP {$response->status()}");
        }
        
        $shop = $response->json()['shop'];
        
        return [
            'id' => $shop['id'],
            'name' => $shop['name'],
            'currency' => $shop['currency'],
            'country_code' => $shop['country_code'] ?? null,
            'timezone' => $shop['iana_timezone'] ?? 'UTC',
            'plan_name' => $shop['plan_name'] ?? 'basic'
        ];
    }
}