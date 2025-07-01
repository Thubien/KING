<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',
        'store_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'product_name',
        'product_sku',
        'quantity',
        'refund_amount',
        'currency',
        'return_reason',
        'status',
        'resolution',
        'notes',
        'tracking_number',
        'customer_tracking_number',
        'handled_by',
        'media',
        // Finansal alanlar
        'refund_method',
        'store_credit_amount',
        'store_credit_code',
        'transaction_id',
        'creates_financial_record',
        'exchange_product_name',
        'exchange_product_sku',
        'exchange_product_price',
        'exchange_difference',
    ];

    protected $casts = [
        'media' => 'array',
        'refund_amount' => 'decimal:2',
        'store_credit_amount' => 'decimal:2',
        'exchange_product_price' => 'decimal:2',
        'exchange_difference' => 'decimal:2',
        'quantity' => 'integer',
        'creates_financial_record' => 'boolean',
    ];

    const STATUSES = [
        'pending' => 'Beklemede',
        'in_transit' => 'Yolda',
        'processing' => 'İşlemde',
        'completed' => 'Tamamlandı',
    ];

    const RESOLUTIONS = [
        'refund' => 'Para İadesi',
        'exchange' => 'Değişim',
        'store_credit' => 'Mağaza Kredisi',
        'rejected' => 'Reddedildi',
    ];

    const REFUND_METHODS = [
        'cash' => 'Nakit İade',
        'exchange' => 'Değişim',
        'store_credit' => 'Mağaza Kredisi',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function checklists()
    {
        return $this->hasMany(ReturnChecklist::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function storeCredit()
    {
        return $this->hasOne(StoreCredit::class);
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getStatusColorAttribute()
    {
        return [
            'pending' => 'gray',
            'in_transit' => 'yellow',
            'processing' => 'blue',
            'completed' => 'green',
        ][$this->status] ?? 'gray';
    }

    public function getCompletionPercentageAttribute()
    {
        $total = $this->checklists->where('stage', $this->status)->count();
        $checked = $this->checklists->where('stage', $this->status)->where('is_checked', true)->count();

        return $total > 0 ? round(($checked / $total) * 100) : 0;
    }

    public function getStatusLabelAttribute()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getResolutionLabelAttribute()
    {
        return self::RESOLUTIONS[$this->resolution] ?? $this->resolution;
    }

    public function getRefundMethodLabelAttribute()
    {
        return self::REFUND_METHODS[$this->refund_method] ?? $this->refund_method;
    }

    /**
     * Bu iade için finansal kayıt oluşturulmalı mı?
     */
    public function shouldCreateFinancialRecord(): bool
    {
        // Store ilişkisi yüklü değilse yükle
        if (!$this->relationLoaded('store')) {
            $this->load('store');
        }

        // Store yoksa false dön
        if (!$this->store) {
            return false;
        }

        // Kural 1: Shopify mağazalar için asla finansal kayıt oluşturma
        if ($this->store->platform === 'shopify') {
            return false;
        }

        // Kural 2: Sadece butik/fiziksel mağazalar ve nakit iade için finansal kayıt
        if (in_array($this->store->platform, ['physical', 'boutique']) && $this->refund_method === 'cash') {
            return true;
        }

        return false;
    }

    /**
     * Finansal transaction tutarını hesapla
     */
    public function getFinancialAmount(): float
    {
        switch ($this->refund_method) {
            case 'cash':
                // Nakit iade = negatif tutar (kasadan çıkış)
                return -abs($this->refund_amount);
            
            case 'exchange':
                // Değişim farkı varsa
                return $this->exchange_difference ?? 0;
            
            case 'store_credit':
                // Store credit = finansal etki yok
                return 0;
            
            default:
                return 0;
        }
    }

    /**
     * Finansal transaction oluştur
     */
    public function createFinancialTransaction(): ?Transaction
    {
        // Store ilişkisi yüklü değilse yükle
        if (!$this->relationLoaded('store')) {
            $this->load('store');
        }

        // Store yoksa null dön
        if (!$this->store) {
            return null;
        }

        // Shopify kontrolü - ÇOK KRİTİK!
        if ($this->store->platform === 'shopify') {
            return null;
        }

        // Zaten transaction varsa tekrar oluşturma
        if ($this->transaction_id) {
            return $this->transaction;
        }

        // Finansal kayıt gerekmiyor mu kontrol et
        if (!$this->shouldCreateFinancialRecord()) {
            // Sadece not amaçlı kayıt (değişim veya store credit)
            if (in_array($this->refund_method, ['exchange', 'store_credit'])) {
                $transaction = Transaction::create([
                    'company_id' => $this->company_id,
                    'store_id' => $this->store_id,
                    'category' => 'RETURNS',
                    'amount' => 0, // Finansal etki yok
                    'currency' => $this->currency,
                    'description' => $this->generateTransactionDescription(),
                    'transaction_date' => now(),
                    'is_verified' => true,
                    'metadata' => [
                        'return_request_id' => $this->id,
                        'refund_method' => $this->refund_method,
                        'note_only' => true,
                    ],
                ]);

                $this->update(['transaction_id' => $transaction->id]);
                return $transaction;
            }
            
            return null;
        }

        // Nakit iade transaction'ı
        $transaction = Transaction::create([
            'company_id' => $this->company_id,
            'store_id' => $this->store_id,
            'category' => 'RETURNS',
            'amount' => $this->getFinancialAmount(),
            'currency' => $this->currency,
            'description' => $this->generateTransactionDescription(),
            'transaction_date' => now(),
            'is_verified' => true,
            'reference_number' => "RETURN-{$this->id}",
            'metadata' => [
                'return_request_id' => $this->id,
                'refund_method' => 'cash',
                'customer_name' => $this->customer_name,
                'product_name' => $this->product_name,
            ],
        ]);

        $this->update([
            'transaction_id' => $transaction->id,
            'creates_financial_record' => true,
        ]);

        return $transaction;
    }

    /**
     * Store credit oluştur
     */
    public function createStoreCredit(): ?StoreCredit
    {
        if ($this->refund_method !== 'store_credit') {
            return null;
        }

        // Zaten store credit varsa tekrar oluşturma
        if ($this->store_credit_code) {
            return $this->storeCredit;
        }

        $code = $this->generateStoreCreditCode();
        
        $storeCredit = StoreCredit::create([
            'company_id' => $this->company_id,
            'store_id' => $this->store_id,
            'return_request_id' => $this->id,
            'code' => $code,
            'amount' => $this->refund_amount,
            'remaining_amount' => $this->refund_amount,
            'currency' => $this->currency,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email ?? 'N/A',
            'customer_phone' => $this->customer_phone,
            'status' => 'active',
            'expires_at' => now()->addYear(), // 1 yıl geçerlilik
            'issued_by' => auth()->user()->name ?? 'System',
            'notes' => "Return Request #{$this->id} - {$this->product_name}",
        ]);

        $this->update([
            'store_credit_code' => $code,
            'store_credit_amount' => $this->refund_amount,
        ]);

        return $storeCredit;
    }

    /**
     * Transaction açıklaması oluştur
     */
    private function generateTransactionDescription(): string
    {
        return match($this->refund_method) {
            'cash' => "Nakit İade - {$this->product_name} (#{$this->id})",
            'exchange' => "Değişim - {$this->product_name} → {$this->exchange_product_name} (Finansal etki yok)",
            'store_credit' => "Store Credit - {$this->store_credit_code} ({$this->customer_name})",
            default => "İade #{$this->id} - {$this->product_name}"
        };
    }

    /**
     * Benzersiz store credit kodu oluştur
     */
    private function generateStoreCreditCode(): string
    {
        do {
            $code = 'SC-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (StoreCredit::where('code', $code)->exists());

        return $code;
    }

    /**
     * İade tamamlandığında çalışacak işlemler
     */
    public function complete(): void
    {
        // Validasyon: Zaten tamamlanmış mı?
        if ($this->status === 'completed') {
            throw new \Exception('Bu iade zaten tamamlanmış.');
        }

        // Validasyon: Gerekli alanlar dolu mu?
        if (!$this->refund_method) {
            throw new \Exception('İade yöntemi seçilmemiş.');
        }

        if ($this->refund_method === 'exchange' && !$this->exchange_product_name) {
            throw new \Exception('Değişim için yeni ürün bilgisi girilmemiş.');
        }

        if ($this->refund_method === 'store_credit' && !$this->customer_email) {
            throw new \Exception('Store credit için müşteri e-posta adresi gerekli.');
        }

        // Transaction kontrolü
        \DB::beginTransaction();
        
        try {
            $this->update(['status' => 'completed']);

            // Finansal işlemleri başlat
            if ($this->refund_method === 'cash') {
                $this->createFinancialTransaction();
            } elseif ($this->refund_method === 'store_credit') {
                $this->createStoreCredit();
                $this->createFinancialTransaction(); // Not amaçlı
            } elseif ($this->refund_method === 'exchange') {
                $this->createFinancialTransaction(); // Not amaçlı
            }
            
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * İade iptal edilebilir mi?
     */
    public function canBeCancelled(): bool
    {
        // Tamamlanmış veya finansal kayıt oluşmuş iadeleri iptal edemeyiz
        if ($this->status === 'completed' || $this->transaction_id) {
            return false;
        }

        // Store credit oluşmuşsa iptal edemeyiz
        if ($this->store_credit_code) {
            return false;
        }

        return true;
    }
    
    /**
     * Create or link customer from return request data
     */
    public function createOrLinkCustomer(): void
    {
        if ($this->customer_id || (!$this->customer_phone && !$this->customer_email)) {
            return;
        }
        
        // Find or create customer
        $customer = Customer::findOrCreateFromData([
            'name' => $this->customer_name,
            'phone' => $this->customer_phone,
            'email' => $this->customer_email,
            'source' => 'return',
            'notes' => "İlk kayıt: İade talebi #{$this->id}",
        ], $this->store);
        
        if ($customer) {
            $this->customer_id = $customer->id;
            $this->saveQuietly(); // Don't trigger events again
            
            // Log timeline event
            $customer->logTimelineEvent(
                'return_requested',
                'İade talebi oluşturuldu',
                "Ürün: {$this->product_name}, Tutar: {$this->refund_amount} {$this->store->currency}",
                [
                    'product_name' => $this->product_name,
                    'refund_amount' => $this->refund_amount,
                    'currency' => $this->store->currency,
                    'return_reason' => $this->return_reason,
                ],
                'ReturnRequest',
                $this->id
            );
        }
    }
    
    // Boot method
    protected static function boot()
    {
        parent::boot();
        
        // Multi-tenant scoping
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                
                // Super admin can see all return requests
                if ($user->hasRole('super_admin')) {
                    return;
                }
                
                // Other users only see their company's return requests
                if ($user->company_id) {
                    $builder->where('company_id', $user->company_id);
                }
            }
        });
        
        static::created(function ($returnRequest) {
            // Create or link customer
            $returnRequest->createOrLinkCustomer();
        });
        
        static::updated(function ($returnRequest) {
            // When completed, update customer statistics
            if ($returnRequest->wasChanged('status') && $returnRequest->status === 'completed' && $returnRequest->customer_id) {
                $returnRequest->customer->updateStatistics();
                
                // Log timeline event
                $returnRequest->customer->logTimelineEvent(
                    'return_completed',
                    'İade tamamlandı',
                    "İade yöntemi: {$returnRequest->refund_method_label}",
                    [
                        'refund_method' => $returnRequest->refund_method,
                        'refund_amount' => $returnRequest->refund_amount,
                        'store_credit_code' => $returnRequest->store_credit_code,
                    ],
                    'ReturnRequest',
                    $returnRequest->id
                );
            }
        });
    }

}
