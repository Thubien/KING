<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Store;

class ShopifyConnectionWidget extends Widget implements HasForms
{
    use InteractsWithForms;
    
    protected static string $view = 'filament.widgets.shopify-connection';
    
    protected int | string | array $columnSpan = 'full';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🛒 Connect Shopify Store')
                    ->description('Connect your Shopify store to sync transactions and manage partnerships')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('shop_domain')
                                    ->label('Shopify Store URL')
                                    ->placeholder('your-store.myshopify.com')
                                    ->helperText('Enter your Shopify store domain (e.g., my-awesome-store.myshopify.com)')
                                    ->required()
                                    ->rule('regex:/^[a-zA-Z0-9\-]+\.myshopify\.com$|^[a-zA-Z0-9\-]+$/')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state && !str_ends_with($state, '.myshopify.com')) {
                                            $set('shop_domain', $state . '.myshopify.com');
                                        }
                                    }),
                                    
                                Forms\Components\Placeholder::make('connection_info')
                                    ->label('')
                                    ->content(function () {
                                        $user = auth()->user();
                                        $company = $user->company;
                                        $connectedStores = $company->stores()->count();
                                        $maxStores = config('shopify.store_limits.' . $company->plan, 3);
                                        
                                        return "**Current Plan:** {$company->plan}
**Connected Stores:** {$connectedStores}/{$maxStores}
**Available:** " . ($maxStores - $connectedStores) . " store connections";
                                    }),
                            ]),
                            
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('connect')
                                ->label('Connect Shopify Store')
                                ->color('primary')
                                ->icon('heroicon-o-link')
                                ->action('connectStore')
                                ->requiresConfirmation()
                                ->modalHeading('Connect Shopify Store')
                                ->modalDescription('You will be redirected to Shopify to authorize the connection. Make sure you have admin access to the store.')
                                ->modalSubmitActionLabel('Connect Now'),
                        ])
                        ->alignCenter(),
                    ])
                    ->collapsible()
                    ->collapsed(fn () => $this->hasConnectedStores()),
            ])
            ->statePath('data');
    }
    
    public function connectStore(): void
    {
        $data = $this->form->getState();
        
        if (empty($data['shop_domain'])) {
            $this->addError('shop_domain', 'Please enter a Shopify store domain.');
            return;
        }
        
        // Redirect to Shopify connection
        $this->redirect(route('shopify.connect', ['shop_domain' => $data['shop_domain']]), navigate: false);
    }
    
    public function getConnectedStores()
    {
        $user = auth()->user();
        return $user->company->stores()
            ->where('status', 'active')
            ->whereNotNull('shopify_domain')
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    public function hasConnectedStores(): bool
    {
        return $this->getConnectedStores()->isNotEmpty();
    }
    
    public static function canView(): bool
    {
        $user = auth()->user();
        return $user?->isCompanyOwner() || $user?->isAdmin();
    }
}