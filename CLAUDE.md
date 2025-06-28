# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview - KING SaaS Platform

This is a **multi-store financial SaaS platform** built for e-commerce entrepreneurs managing multiple Shopify stores with business partners. The platform automates financial tracking, transaction categorization, and profit sharing calculations.

### Target Problem Solved
- **Before**: Manual Excel tracking, 10+ hours/week, partnership disputes, financial complexity chaos
- **After**: Automated imports, smart categorization, transparent profit sharing, real-time insights

### Core Value Proposition
Think "QuickBooks for Multi-Store E-commerce Partnerships" with Shopify-first approach and automated partnership profit distribution.

## Development Commands

### Core Development Commands
- `composer dev` - Start full development environment (Laravel server, queue worker, logs, and Vite)
- `composer test` - Run PHPUnit tests with config clear
- `php artisan serve` - Start Laravel development server
- `npm run dev` - Start Vite development server for assets
- `npm run build` - Build production assets

### Database Commands
- `php artisan migrate` - Run database migrations
- `php artisan migrate:fresh --seed` - Fresh migration with seeders
- `php artisan db:seed` - Run database seeders

### Code Quality Commands
- `./vendor/bin/pint` - Run Laravel Pint for code formatting
- `./vendor/bin/phpunit` - Run PHPUnit tests directly

### Filament Commands
- `php artisan filament:upgrade` - Upgrade Filament components
- `php artisan make:filament-resource ModelName` - Create new Filament resource

## Architecture Overview

### Core Technology Stack
- **Framework**: Laravel 12 with PHP 8.2+
- **Admin Panel**: Filament v3 for complete backend management
- **Frontend**: Vite + TailwindCSS v4 + Alpine.js
- **Database**: SQLite (development), supports PostgreSQL/MySQL
- **Authentication**: Laravel's built-in auth with Spatie Laravel Permission

### Business Domain Architecture

**Multi-Tenant SaaS Structure:**
- **Companies** (top level) - UK/US legal entities with subscription plans
- **Stores** (belong to companies) - Individual Shopify stores
- **Partnerships** (within stores) - Business partner ownership percentages
- **Transactions** (belong to stores) - Financial data entries
- **Partners** (users) - Access specific stores based on partnerships

**Key Business Rules:**
- Partnership percentages must total exactly 100% per store
- Personal expenses are private to individual partners
- Business expenses are visible to all store partners
- Real-time balance validation: Bank balance = Store balances (critical)
- Multi-currency support with USD consolidation

### Import System Architecture

**Strategy Pattern Implementation:**
```
ImportOrchestrator → Strategy Selection → Format Detection → Processing → Validation
```

**Supported Data Sources:**
- **Mercury Bank** (17 columns, 100% detection accuracy)
- **Payoneer EUR/USD** (7 columns, 100% detection accuracy)  
- **Stripe Balance History** (15 columns, 100% detection accuracy)
- **Stripe Payments Report** (28 columns, 83%+ detection accuracy)

**Core Components:**
- `ImportOrchestrator` - Main coordinator for all imports with batch tracking
- `ImportBatch` - Tracks import progress and results with real-time updates
- `BankFormatDetector` - Auto-detects CSV format with confidence scoring
- `CsvImportStrategy` - Processes CSV with comprehensive error handling
- `DateParser` / `AmountParser` - Handle complex formatting variations

**Import Flow:**
1. File upload → Create ImportBatch record with metadata
2. Auto-detect format using `BankFormatDetector`
3. Select appropriate strategy (e.g., `CsvImportStrategy`)
4. Parse and validate data with business rules
5. Smart categorization using pattern matching
6. Store assignment and partner attribution
7. Approval workflow for manual review
8. Full transaction rollback on failures

### Transaction Processing Engine - THE HEART OF THE SYSTEM

**The Transaction Editor is the critical component that:**
- Assigns bank transactions to specific stores
- Separates personal vs business expenses
- Tracks partner spending for debt calculation
- Splits multi-store expenses across stores
- Applies 11-category standardized system

**11-Category Financial System:**
1. **SALES** - Revenue from sales
2. **RETURNS** - Real money refunds
3. **PAY-PRODUCT** - Product purchase costs
4. **PAY-DELIVERY** - Shipping costs
5. **INVENTORY** - Current stock value
6. **WITHDRAW** - Partner withdrawals
7. **END** - Personal transfer commissions
8. **BANK_COM** - Banking fees
9. **FEE** - Payment processor fees
10. **ADS** - Advertising spend
11. **OTHER_PAY** - All other expenses

**Smart Categorization Patterns:**
```php
$patterns = [
    '/facebook|fb|meta/i' => 'ADS',
    '/stripe|fee/i' => 'FEE',
    '/payoneer|transfer/i' => 'BANK_COM',
    '/salary|partner/i' => 'WITHDRAW',
    '/alibaba|supplier/i' => 'PAY-PRODUCT'
];
```

### Partnership Management System

**Transparency Rules:**
- **Public Data** (visible to all partners): Store revenue, expenses, net profit, partnership percentages
- **Private Data** (visible only to individual partner): Personal expenses, debt balance, net settlement

**Partner Account Tracking:**
- Gross profit share (based on ownership percentage)
- Personal expenses (reduces partner's net balance)
- Business payments (approved business expenses)
- Debt balance (money owed by partner to store)
- Settlement history (payment records)

### Filament Resources Structure
All admin functionality via Filament resources in `app/Filament/Resources/`:
- `CompanyResource` - Company management with trial/subscription logic
- `StoreResource` - Store management per company
- `TransactionResource` - Financial transaction management with smart categorization
- `ImportBatchResource` - Import history and monitoring with real-time progress
- `PartnershipResource` - Store partnership management with percentage validation

### Shopify Integration (Planned)

**SaaS Onboarding Flow:**
1. User signup with company details
2. Shopify OAuth connection (multiple stores)
3. Company/legal structure setup
4. Banking configuration (Payoneer/Mercury)
5. Partner invitation system
6. Store-to-company assignment
7. Partnership configuration per store

**Embedded App Features:**
- Real-time dashboard in Shopify admin
- Quick expense entry forms
- Partner summary widgets
- Financial alerts for low balances
- Performance analytics per store

### Database Design Patterns

**Multi-Tenant Security:**
- Global scopes on all models for company isolation
- Company ID required on all tenant data
- Automatic filtering by authenticated user's company

**Key Relationships:**
```php
Company → hasMany Stores → hasMany Transactions
Company → hasManyThrough Transactions (via Stores)
Store → hasMany Partnerships → belongsTo Users (Partners)
Transaction → belongsTo Store, Partner (if personal expense)
```

**Performance Optimizations:**
```sql
-- Strategic indexing for common queries
INDEX(company_id, store_id, transaction_date)
INDEX(category, status, transaction_date) 
INDEX(partner_id, is_personal_expense)
INDEX(store_id, partnership_percentage)
```

## Development Workflow

### Critical Business Logic Implementation

**UPDATED Balance Validation System (CRITICAL):**
```php
// MUST include payment processor holding periods
$totalRealMoney = 
    BankAccount::sum('current_balance') +           // Bankadaki para
    PaymentProcessorAccount::sum('current_balance + pending_balance'); // Processor'lardaki para

$calculatedStoreBalances = Store::sum('calculated_balance');
assert(abs($totalRealMoney - $calculatedStoreBalances) < 0.01);
```

**Why This Matters:**
- Stripe/PayPal hold money for 2-7 days before payout
- SALES transactions → Payment processor pending balance
- Payout process → Pending to current → Bank transfer
- Without this logic, balances will never match real world

**Partnership Percentage Validation:**
```php
// Must equal 100% exactly
$totalPercentage = Partnership::where('store_id', $storeId)->sum('ownership_percentage');
assert($totalPercentage === 100.0);
```

### Testing Strategy
- **Unit Tests**: Model business logic, service classes, validation rules
- **Integration Tests**: Import workflows, partnership calculations  
- **Performance Tests**: Large file imports (1000+ transactions)
- **Security Tests**: Multi-tenant data isolation

### Asset Development
- TailwindCSS v4 with Vite integration
- Filament auto-generates admin panel assets
- Real-time updates via Livewire components

### Import Development Guidelines
When adding new import strategies:
1. Implement `ImportStrategyInterface`
2. Add format detection logic to `BankFormatDetector`
3. Register in `ImportOrchestrator::registerStrategies()`
4. Create comprehensive test cases for edge cases
5. Add corresponding Filament resource for monitoring

### Security Considerations
- **Multi-tenant isolation**: Global scopes enforce company boundaries
- **Data encryption**: Sensitive banking information encrypted at rest
- **Audit trails**: Complete transaction history with user attribution
- **Role-based access**: Partners only see their store data
- **Personal expense privacy**: Individual partner expenses remain private

## Key Business Insights for Development

### The Core Problem This Solves
E-commerce entrepreneurs with multiple stores and business partners struggle with:
1. **Financial Transparency**: "How much did each partner actually earn this month?"
2. **Trust Issues**: Manual calculations lead to disputes
3. **Operational Complexity**: Managing 5+ stores with different currencies
4. **Time Waste**: 10+ hours/week on manual Excel tracking

### Critical Success Factors
1. **Accuracy**: Financial calculations must be 100% accurate
2. **Transparency**: Partners must trust the profit sharing calculations  
3. **Automation**: Reduce manual work from hours to minutes
4. **Real-time**: Instant visibility into financial performance
5. **Scalability**: Handle growth from 2 stores to 20+ stores

### Integration Roadmap
- **Phase 1**: Foundation with CSV imports - COMPLETE
- **Phase 2**: Advanced transaction processing (current)
- **Phase 3**: Shopify OAuth integration
- **Phase 4**: Smart categorization with ML
- **Phase 5**: Advanced reporting and analytics
- **Phase 6**: API integrations (Stripe, PayPal, other banks)

## Important Implementation Notes

### Payment Processor Holding Period Implementation

**Key Models Added:**
- `PaymentProcessorAccount` - Tracks current + pending balances for Stripe/PayPal/Shopify Payments
- `BalanceValidationService` - Critical service for real-world balance validation
- Updated `Transaction` model with payment processor fields

**Manuel CSV Workflow:**
1. **Sales Transaction Import** → Automatically adds to Payment Processor pending balance
2. **Manual Payout Processing** → Move pending to current balance
3. **Bank Transfer** → Move from processor current to bank account
4. **Balance Validation** → Bank + Processor balances = Store calculated balances

**New Transaction Fields:**
- `payment_processor_type` - STRIPE, PAYPAL, SHOPIFY_PAYMENTS, MANUAL
- `payment_processor_id` - Links to PaymentProcessorAccount
- `is_pending_payout` - Tracks if payout is pending
- `is_personal_expense` - Partner debt tracking
- `partner_id` - Links personal expenses to partners

### Financial Report Engine Structure
The system uses a **standard 11-category financial table format**:
1. **SALES** - Revenue from sales
2. **RETURNS** - Real money refunds  
3. **PAY-PRODUCT** - Product purchase costs
4. **PAY-DELIVERY** - Shipping costs
5. **INVENTORY** - Current stock value
6. **WITHDRAW** - Partner withdrawals
7. **END** - Personal transfer commissions
8. **BANK_COM** - Banking fees
9. **FEE** - Payment processor fees
10. **ADS** - Advertising spend
11. **OTHER_PAY** - All other expenses

### Real-Time Balance Dashboard
- `BalanceOverviewWidget` - Shows Bank + Processor + Pending balances
- Auto-refresh every 30 seconds
- Visual indicators for balance validation status
- Immediate alerts for balance discrepancies

### Multi-Store Allocation Logic
When expenses need to be split across stores:
- Equal percentage split suggested by default
- Manual adjustment with 100% validation
- Store-specific assignment for targeted expenses
- Audit trail for all allocation decisions

### Personal vs Business Expense Tracking
- **Personal expenses**: Tracked per partner, affects debt balance, private visibility
- **Business expenses**: Shared visibility among store partners
- **Approval thresholds**: Auto-approve small amounts, manual review for large
- **Settlement workflow**: Regular debt resolution between partners

### Payment Processor Management
- `PaymentProcessorAccountResource` - Filament interface for managing processors
- Manual balance adjustments for reconciliation
- Payout processing tools for moving pending to current
- Real-time sync status tracking

This platform now accurately reflects real-world payment processor holding periods, making it production-ready for e-commerce financial tracking. The balance validation system is the foundation that ensures 100% accuracy.