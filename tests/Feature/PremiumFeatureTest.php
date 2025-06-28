<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use App\Services\Import\Strategies\StripeApiStrategy;

class PremiumFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_cannot_use_api_integrations()
    {
        // Create company with starter plan (maps to free)
        $company = Company::factory()->create([
            'plan' => 'starter',
            'is_trial' => false,
            'api_integrations_enabled' => false
        ]);

        $this->assertFalse($company->canUseApiIntegrations());
        $this->assertEquals('free', $company->getSubscriptionPlan());
        $this->assertEquals(0, $company->getMaxApiCallsPerMonth());
    }

    public function test_professional_plan_can_use_api_integrations()
    {
        $company = Company::factory()->create([
            'plan' => 'professional',
            'is_trial' => false,
            'api_integrations_enabled' => false // Should still work due to plan
        ]);

        $this->assertTrue($company->canUseApiIntegrations());
        $this->assertEquals('premium', $company->getSubscriptionPlan());
        $this->assertEquals(10000, $company->getMaxApiCallsPerMonth());
    }

    public function test_enterprise_plan_has_maximum_features()
    {
        $company = Company::factory()->create([
            'plan' => 'enterprise',
            'is_trial' => false
        ]);

        $this->assertTrue($company->canUseApiIntegrations());
        $this->assertTrue($company->canUseWebhooks());
        $this->assertTrue($company->canUseRealTimeSync());
        $this->assertEquals('enterprise', $company->getSubscriptionPlan());
        $this->assertEquals(100000, $company->getMaxApiCallsPerMonth());
    }

    public function test_trial_companies_get_premium_features()
    {
        $company = Company::factory()->create([
            'plan' => 'starter',
            'is_trial' => true,
            'trial_ends_at' => now()->addDays(7)
        ]);

        $this->assertTrue($company->canUseApiIntegrations());
        $this->assertTrue($company->canUseWebhooks());
        $this->assertTrue($company->canUseRealTimeSync());
    }

    public function test_api_usage_tracking()
    {
        $company = Company::factory()->create([
            'plan' => 'professional',
            'api_calls_this_month' => 5000,
            'max_api_calls_per_month' => 0 // Use plan default
        ]);

        $this->assertEquals(5000, $company->getRemainingApiCalls());
        $this->assertFalse($company->isApiLimitExceeded());

        // Increment usage
        $company->incrementApiUsage(100);
        $company->refresh();

        $this->assertEquals(4900, $company->getRemainingApiCalls());
        $this->assertEquals(5100, $company->api_calls_this_month);
    }

    public function test_api_limit_exceeded()
    {
        $company = Company::factory()->create([
            'plan' => 'professional',
            'api_calls_this_month' => 10000 // At limit
        ]);

        $this->assertEquals(0, $company->getRemainingApiCalls());
        $this->assertTrue($company->isApiLimitExceeded());
    }

    public function test_stripe_api_strategy_premium_check()
    {
        // Create free plan company
        $company = Company::factory()->create([
            'plan' => 'starter',
            'is_trial' => false,
            'api_integrations_enabled' => false
        ]);

        $store = Store::factory()->create([
            'company_id' => $company->id
        ]);

        $strategy = new StripeApiStrategy();
        $result = $strategy->import(['secret_key' => 'sk_test_dummy'], $store);

        $this->assertEquals(0, $result->successfulRecords);
        $this->assertStringContainsString('Premium plans', $result->errorMessage);
    }

    public function test_monthly_api_reset()
    {
        $company = Company::factory()->create([
            'plan' => 'professional',
            'api_calls_this_month' => 5000
        ]);

        $company->resetMonthlyApiUsage();
        $company->refresh();

        $this->assertEquals(0, $company->api_calls_this_month);
        $this->assertEquals(10000, $company->getRemainingApiCalls());
    }
}