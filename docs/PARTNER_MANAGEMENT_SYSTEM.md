# Phase 4A: Partner Management System

## Overview
The Partner Management System allows companies to invite external partners, manage their access to specific stores, and track profit sharing. Partners have their own dedicated dashboard with complete data isolation.

## Key Features
- 🔐 **Secure Invitation System** - Token-based email invitations with 7-day expiration
- 🎯 **Role-Based Access Control** - Partners can only access their assigned stores
- 📊 **Partner Dashboard** - Dedicated interface showing relevant metrics and data
- 🔒 **Complete Data Isolation** - Partners cannot see other partners' data
- ⚡ **Optimized Performance** - Fast queries with minimal database load

## Architecture

### Database Structure

#### Partnerships Table
```sql
- id (primary key)
- store_id (foreign key to stores)
- user_id (nullable foreign key to users)
- partner_email (email for invitation)
- invitation_token (unique 64-char token)
- status (PENDING_INVITATION, ACTIVE, INACTIVE)
- ownership_percentage (decimal)
- profit_share_percentage (decimal)
- invited_at (timestamp)
- activated_at (timestamp)
```

#### Key Relationships
- Partnership belongs to Store
- Partnership belongs to User (partner)
- User has many Partnerships
- Store has many Partnerships

### Authentication & Authorization

#### Roles
- **company_owner** - Full access to all company data
- **partner** - Limited access to assigned stores only
- **staff** - Basic access (future implementation)

#### Permissions
Partners are restricted through:
1. **Policy-based authorization** (StorePolicy, PartnershipPolicy, TransactionPolicy)
2. **Global query scopes** in models
3. **Middleware** (EnsureUserAccess)

## Implementation Guide

### 1. Partner Invitation Flow

#### Step 1: Create Partnership with Email
```php
// In PartnershipResource or controller
$partnership = Partnership::create([
    'store_id' => $storeId,
    'partner_email' => 'partner@example.com',
    'ownership_percentage' => 25.00,
    'status' => 'PENDING_INVITATION'
]);

$partnership->generateInvitationToken();
$partnership->sendInvitationEmail();
```

#### Step 2: Partner Signup
```php
// Route: /partner/signup/{token}
public function signup(Request $request, $token)
{
    $partnership = Partnership::where('invitation_token', $token)
        ->where('status', 'PENDING_INVITATION')
        ->firstOrFail();
    
    if (!$partnership->isInvitationValid()) {
        abort(410, 'Invitation expired');
    }
    
    // Create user and activate partnership
    $user = User::create($validatedData);
    $partnership->activatePartnership($user);
}
```

### 2. Data Isolation Implementation

#### User Model Methods
```php
public function hasStoreAccess(int $storeId): bool
{
    if ($this->isAdmin() || $this->isCompanyOwner()) {
        return true;
    }
    
    return $this->partnerships()
        ->where('store_id', $storeId)
        ->where('status', 'ACTIVE')
        ->exists();
}

public function getAccessibleStoreIds(): array
{
    if ($this->isAdmin() || $this->isCompanyOwner()) {
        return $this->company->stores()->pluck('id')->toArray();
    }
    
    return $this->partnerships()
        ->where('status', 'ACTIVE')
        ->pluck('store_id')
        ->toArray();
}
```

#### Policy Example
```php
// app/Policies/StorePolicy.php
public function view(User $user, Store $store): bool
{
    return $user->hasStoreAccess($store->id);
}

public function viewAny(User $user): bool
{
    return $user->isCompanyOwner() || 
           $user->isAdmin() || 
           $user->partnerships()->where('status', 'ACTIVE')->exists();
}
```

### 3. Partner Dashboard

#### Custom Filament Page
```php
// app/Filament/Pages/PartnerDashboard.php
class PartnerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static string $view = 'filament.pages.partner-dashboard';
    
    public static function canAccess(): bool
    {
        return auth()->user()->isPartner();
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            PartnerStoresWidget::class,
            PartnerRevenueWidget::class,
            PartnerProfitShareWidget::class,
        ];
    }
}
```

#### Widgets for Partners
```php
// app/Filament/Widgets/PartnerStoresWidget.php
protected function getStats(): array
{
    $user = auth()->user();
    $storeIds = $user->getAccessibleStoreIds();
    
    return [
        Stat::make('My Stores', count($storeIds)),
        Stat::make('Total Ownership', $user->getTotalOwnershipPercentage() . '%'),
        Stat::make('Monthly Profit Share', '$' . number_format($user->getTotalMonthlyProfitShare(), 2)),
    ];
}
```

## API Reference

### Partnership Model Methods

#### `generateInvitationToken(): string`
Generates a secure 64-character invitation token.

#### `sendInvitationEmail(): void`
Sends invitation email using PartnerInvitationMail.

#### `isInvitationValid(): bool`
Checks if invitation is within 7-day validity period.

#### `activatePartnership(User $user): void`
Activates partnership and assigns partner role to user.

### User Model Methods

#### `isPartner(): bool`
Checks if user type is 'partner'.

#### `hasStoreAccess(int $storeId): bool`
Verifies if user can access specific store.

#### `getAccessibleStoreIds(): array`
Returns array of store IDs user can access.

#### `getTotalMonthlyProfitShare(): float`
Calculates total monthly profit share across all partnerships.

## Security Features

### 1. Token Security
- 64-character cryptographically secure tokens
- 7-day expiration period
- One-time use (deleted after activation)

### 2. Data Isolation
- Global query scopes prevent cross-company data access
- Policy-based authorization on all resources
- Middleware verification for route access

### 3. Input Validation
- Email validation for invitations
- Ownership percentage limits (0-100%)
- Token format validation

## Performance Optimizations

### 1. Query Optimization
- Eager loading relationships to prevent N+1 queries
- Indexed database columns for fast lookups
- Optimized dashboard widgets

### 2. Caching Strategies
```php
// Cache user's accessible store IDs
public function getAccessibleStoreIds(): array
{
    return Cache::remember(
        "user:{$this->id}:accessible_stores",
        now()->addMinutes(10),
        fn() => $this->calculateAccessibleStoreIds()
    );
}
```

### 3. Performance Metrics
- Dashboard queries: ~2-4ms
- Data isolation checks: ~3-5ms for 15 partners
- Query count: Only 6 queries for full dashboard load

## Testing

### Test Coverage
- **PartnerInvitationTest**: 5 tests covering invitation flow
- **PartnerDataIsolationTest**: 6 tests covering data access restrictions
- **PartnerDashboardPerformanceTest**: 3 tests covering performance benchmarks

### Running Tests
```bash
# Run all partner tests
php artisan test tests/Feature/Partner*

# Run specific test suite
php artisan test tests/Feature/PartnerInvitationTest.php
php artisan test tests/Feature/PartnerDataIsolationTest.php
php artisan test tests/Feature/PartnerDashboardPerformanceTest.php
```

## Deployment Checklist

### Database Migrations
```bash
php artisan migrate
php artisan db:seed --class=PermissionsAndRolesSeeder
```

### Configuration
1. Configure mail settings for invitation emails
2. Set up proper database indexes
3. Configure cache driver for production

### Verification
1. Test invitation flow end-to-end
2. Verify data isolation between partners
3. Check dashboard performance under load
4. Validate email delivery

## Troubleshooting

### Common Issues

#### "Attempt to read property on null"
- **Cause**: Filament trying to access properties on null records
- **Solution**: Add null checks in callback functions
```php
->visible(fn ($record) => $record && $record->status === 'ACTIVE')
```

#### Partner Cannot Access Store
- **Cause**: Partnership not active or expired invitation
- **Solution**: Check partnership status and invitation validity
```php
// Debug partnership status
$partnership = Partnership::where('user_id', $userId)
    ->where('store_id', $storeId)
    ->first();
dd($partnership->status, $partnership->activated_at);
```

#### Slow Dashboard Performance
- **Cause**: N+1 queries or missing indexes
- **Solution**: Enable query log and optimize
```php
\DB::enableQueryLog();
// Run dashboard operations
dd(\DB::getQueryLog());
```

## Future Enhancements

### Phase 4B: Advanced Features
- Partner profit withdrawal system
- Advanced reporting and analytics
- Mobile app support
- Multi-language support

### Phase 4C: Enterprise Features
- Partner onboarding workflows
- Automated commission calculations
- Advanced permissions and roles
- Integration with external accounting systems

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review test cases for examples
3. Check Laravel logs: `storage/logs/laravel.log`
4. Verify database data integrity

## Changelog

### Version 1.0 (Phase 4A)
- Initial partner management system
- Email invitation flow
- Data isolation implementation
- Partner dashboard
- Comprehensive test suite
- Performance optimizations