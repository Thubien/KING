<?php

namespace App\Providers;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Partnership;
use App\Models\PaymentProcessorAccount;
use App\Models\Store;
use App\Models\Transaction;
use App\Policies\BankAccountPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\PartnershipPolicy;
use App\Policies\PaymentProcessorAccountPolicy;
use App\Policies\StorePolicy;
use App\Policies\TransactionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        BankAccount::class => BankAccountPolicy::class,
        Company::class => CompanyPolicy::class,
        Partnership::class => PartnershipPolicy::class,
        PaymentProcessorAccount::class => PaymentProcessorAccountPolicy::class,
        Store::class => StorePolicy::class,
        Transaction::class => TransactionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
