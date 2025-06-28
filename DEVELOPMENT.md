# 💻 **Development Guide - KING SaaS Platform**

> **Comprehensive Developer Documentation for Building and Maintaining the KING Platform**

---

## 🚀 **Quick Start Development**

### **Prerequisites**
- **Docker Desktop** (recommended for consistent environment)
- **PHP 8.3+** with extensions: `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `PDO`, `tokenizer`, `xml`
- **Composer 2.x**
- **Node.js 18+** with npm/yarn
- **Git** for version control

### **Initial Setup**

```bash
# 1. Clone the repository
git clone https://github.com/Thubien/KING.git
cd KING

# 2. Install PHP dependencies
composer install

# 3. Environment setup
cp .env.example .env
php artisan key:generate

# 4. Database setup (SQLite for development)
touch database/database.sqlite
php artisan migrate --seed

# 5. Install Node.js dependencies
npm install

# 6. Start development servers
php artisan serve &  # Backend server
npm run dev         # Frontend assets
```

### **Docker Development (Recommended)**

```bash
# 1. Start Docker environment
./vendor/bin/sail up -d

# 2. Run initial setup
./vendor/bin/sail artisan migrate --seed

# 3. Install frontend dependencies
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev

# Access points:
# Web: http://localhost:8080
# Admin: http://localhost:8080/admin (admin@admin.com / password)
# Database: localhost:3307
# Vite: http://localhost:5174
```

---

## 🏗️ **Development Environment**

### **Docker Services**

```yaml
# docker-compose.yml configuration
services:
  laravel.test:
    ports: ['${APP_PORT:-8080}:80']      # Web server
  mysql:
    ports: ['${FORWARD_DB_PORT:-3307}:3306']  # Database
  vite:
    ports: ['${VITE_PORT:-5174}:5173']   # Asset server
```

### **Environment Variables**

```env
# Core Application
APP_NAME="KING SaaS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

# Docker Database (alternative)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=king_saas
DB_USERNAME=sail
DB_PASSWORD=password

# Filament Admin
FILAMENT_DOMAIN=localhost:8080
FILAMENT_PATH=/admin

# Import System
IMPORT_STORAGE_PATH=storage/app/imports
IMPORT_MAX_FILE_SIZE=10240  # KB
IMPORT_AUTO_CLEANUP_DAYS=30
```

---

## ⚙️ **Development Commands**

### **Core Development**

```bash
# Start development environment
php artisan serve                    # Local development server
./vendor/bin/sail up -d             # Docker environment

# Database management
php artisan migrate                  # Run migrations
php artisan migrate:fresh --seed    # Fresh database with seeders
php artisan migrate:rollback         # Rollback last migration
php artisan db:seed                  # Run seeders only

# Asset compilation
npm run dev                          # Development build with hot reload
npm run build                        # Production build
npm run watch                        # Watch for changes
```

### **Testing & Quality**

```bash
# Testing
php artisan test                     # Run all tests
php artisan test --coverage         # Run with coverage report
php artisan test --filter=CsvTest   # Run specific test class

# Code quality
./vendor/bin/pint                    # Fix code style (Laravel Pint)
./vendor/bin/pint --dirty            # Fix only changed files
./vendor/bin/phpstan analyse         # Static analysis (if installed)

# Cache management
php artisan config:cache             # Cache configuration
php artisan route:cache              # Cache routes
php artisan view:cache               # Cache views
php artisan optimize:clear           # Clear all caches
```

### **Import System Development**

```bash
# Import testing
php artisan tinker
> $result = app(\App\Services\Import\ImportOrchestrator::class)->import('csv', $csvData);

# Import batch monitoring
php artisan tinker
> \App\Models\ImportBatch::latest()->first()->toArray();

# Clear failed imports
php artisan tinker
> \App\Models\ImportBatch::where('status', 'failed')->delete();
```

### **Filament Development**

```bash
# Create new Filament resources
php artisan make:filament-resource ModelName
php artisan make:filament-resource ModelName --view  # Read-only resource

# Create custom Filament pages
php artisan make:filament-page CustomDashboard
php artisan make:filament-page Settings --resource=SettingResource

# Filament optimization
php artisan filament:optimize
php artisan filament:upgrade
```

---

## 📁 **Project Structure Deep Dive**

### **Application Architecture**

```
app/
├── Models/                          # Eloquent models with business logic
│   ├── Company.php                  # Multi-tenant root entity
│   ├── Store.php                    # Store management
│   ├── Partnership.php              # Partnership logic
│   ├── Transaction.php              # Financial transactions
│   ├── ImportBatch.php              # Import tracking
│   └── User.php                     # User management
│
├── Services/                        # Business logic services
│   └── Import/                      # Import system
│       ├── ImportOrchestrator.php   # Central coordinator
│       ├── ImportResult.php         # Result object
│       ├── Contracts/
│       │   └── ImportStrategyInterface.php
│       ├── Detectors/
│       │   └── BankFormatDetector.php
│       ├── Parsers/
│       │   ├── DateParser.php
│       │   └── AmountParser.php
│       └── Strategies/
│           └── CsvImportStrategy.php
│
├── Filament/Resources/              # Admin interface
│   ├── CompanyResource.php
│   ├── StoreResource.php
│   ├── PartnershipResource.php
│   ├── TransactionResource.php
│   └── ImportBatchResource.php
│
└── Providers/
    ├── AppServiceProvider.php      # Service registration
    └── Filament/
        └── AdminPanelProvider.php  # Filament configuration
```

### **Database Schema**

```
database/
├── migrations/
│   ├── 2025_06_28_104424_create_companies_table.php
│   ├── 2025_06_28_104433_create_stores_table.php
│   ├── 2025_06_28_104435_create_partnerships_table.php
│   ├── 2025_06_28_104437_create_transactions_table.php
│   └── 2025_06_28_110541_create_import_batches_table.php
│
├── factories/
│   └── UserFactory.php
│
└── seeders/
    ├── DatabaseSeeder.php
    └── AdminUserSeeder.php
```

---

## 🔧 **Development Workflow**

### **Feature Development Process**

1. **Create Feature Branch**
   ```bash
   git checkout -b feature/payment-integration
   ```

2. **Database Changes**
   ```bash
   php artisan make:migration add_payment_columns_to_transactions_table
   php artisan migrate
   ```

3. **Model Development**
   ```bash
   # Add business logic to models
   # Update relationships
   # Add validation rules
   ```

4. **Service Layer**
   ```bash
   # Create service classes for business logic
   # Register in AppServiceProvider
   ```

5. **Filament Interface**
   ```bash
   php artisan make:filament-resource PaymentResource
   # Customize forms, tables, and validation
   ```

6. **Testing**
   ```bash
   php artisan make:test PaymentFeatureTest
   php artisan test --filter=PaymentFeatureTest
   ```

7. **Documentation**
   ```bash
   # Update README.md
   # Add PHPDoc comments
   # Update API documentation
   ```

### **Import Strategy Development**

Adding support for new CSV formats:

1. **Create Strategy Class**
   ```php
   // app/Services/Import/Strategies/NewBankStrategy.php
   class NewBankStrategy implements ImportStrategyInterface
   {
       public function canHandle(string $format): bool
       {
           return $format === 'new_bank';
       }

       public function process(ImportBatch $batch, $data): ImportResult
       {
           // Implementation
       }
   }
   ```

2. **Update Format Detector**
   ```php
   // app/Services/Import/Detectors/BankFormatDetector.php
   private function isNewBankFormat(array $headers): bool
   {
       $requiredHeaders = ['unique_column', 'another_column'];
       return $this->hasRequiredHeaders($headers, $requiredHeaders);
   }
   ```

3. **Register Strategy**
   ```php
   // app/Providers/AppServiceProvider.php
   $orchestrator->registerStrategy(new NewBankStrategy());
   ```

4. **Add Tests**
   ```php
   public function test_new_bank_format_detection()
   {
       $headers = ['unique_column', 'another_column'];
       $format = $this->detector->detectFormat($headers);
       $this->assertEquals('new_bank', $format);
   }
   ```

---

## 🧪 **Testing Strategy**

### **Test Organization**

```
tests/
├── Feature/                         # Integration tests
│   ├── CompanyManagementTest.php
│   ├── StoreManagementTest.php
│   ├── PartnershipTest.php
│   ├── TransactionTest.php
│   └── ImportSystemTest.php
│
├── Unit/                           # Unit tests
│   ├── Models/
│   │   ├── CompanyTest.php
│   │   ├── StoreTest.php
│   │   └── TransactionTest.php
│   │
│   └── Services/
│       ├── ImportOrchestratorTest.php
│       ├── BankFormatDetectorTest.php
│       ├── DateParserTest.php
│       └── AmountParserTest.php
│
└── TestCase.php                    # Base test class
```

### **Test Examples**

```php
// Feature Test Example
class ImportSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_mercury_csv_import_complete_workflow()
    {
        // Setup
        $company = Company::factory()->create();
        $store = Store::factory()->for($company)->create();
        $user = User::factory()->for($company)->create();
        
        $this->actingAs($user);

        // Test CSV data
        $csvData = $this->getMercuryCsvData();

        // Execute import
        $result = $this->importOrchestrator->import('csv', $csvData);

        // Assert results
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(2, $result->getSuccessfulRecords());
        $this->assertDatabaseHas('transactions', ['amount' => 1234.56]);
    }
}

// Unit Test Example
class AmountParserTest extends TestCase
{
    public function test_payoneer_eur_string_parsing()
    {
        $parser = new AmountParser();
        
        $testCases = [
            '"1,234.56"' => 1234.56,
            '"10,000.00"' => 10000.00,
            '"-500.25"' => -500.25,
        ];

        foreach ($testCases as $input => $expected) {
            $result = $parser->parseAmount($input, 'payoneer', 'EUR');
            $this->assertEquals($expected, $result, "Failed parsing: $input");
        }
    }
}
```

### **Test Data Management**

```php
// Database Factories
class CompanyFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'plan' => fake()->randomElement(['starter', 'professional', 'enterprise']),
            'trial_ends_at' => now()->addDays(14),
            'subscription_active' => true,
        ];
    }
}

// Test Helpers
trait ImportTestHelpers
{
    protected function getMercuryCsvData(): string
    {
        return 'Date (UTC),Description,Amount,Status,Source Account
"2024-12-25 10:30:00","STRIPE TRANSFER",1234.56,"Posted","Checking ***1234"
"2024-12-24 15:45:00","FACEBOOK ADS",-89.50,"Posted","Checking ***1234"';
    }
}
```

---

## 🔍 **Debugging & Troubleshooting**

### **Common Issues & Solutions**

#### **Import System Issues**

```bash
# Debug import batch status
php artisan tinker
> $batch = \App\Models\ImportBatch::latest()->first();
> $batch->toArray();
> $batch->error_details;

# Check format detection
> $detector = app(\App\Services\Import\Detectors\BankFormatDetector::class);
> $headers = ['Date (UTC)', 'Description', 'Amount'];
> $format = $detector->detectFormat($headers);
> echo $format;
```

#### **Multi-Tenancy Issues**

```bash
# Check global scopes
php artisan tinker
> auth()->login(\App\Models\User::first());
> \App\Models\Store::all(); // Should only show user's company stores
> \App\Models\Store::withoutGlobalScope('company')->get(); // All stores
```

#### **Performance Issues**

```bash
# Enable query logging
DB::enableQueryLog();
// Run your operation
dd(DB::getQueryLog());

# Check database indexes
php artisan tinker
> DB::select('SHOW INDEX FROM transactions');
```

### **Logging & Monitoring**

```php
// Import process logging
Log::info('Import started', [
    'batch_id' => $batch->batch_id,
    'format' => $format,
    'file_size' => $fileSize
]);

// Performance monitoring
$startTime = microtime(true);
// ... operation ...
$duration = microtime(true) - $startTime;
Log::info('Operation completed', ['duration' => $duration]);

// Error tracking
try {
    // risky operation
} catch (Exception $e) {
    Log::error('Operation failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'context' => $context
    ]);
}
```

---

## 🚀 **Performance Optimization**

### **Database Optimization**

```php
// Eager loading to prevent N+1 queries
$companies = Company::with(['stores', 'users'])->get();

// Chunk processing for large datasets
Store::chunk(100, function ($stores) {
    foreach ($stores as $store) {
        // Process store
    }
});

// Database indexes for common queries
Schema::table('transactions', function (Blueprint $table) {
    $table->index(['company_id', 'status']);
    $table->index(['store_id', 'transaction_date']);
    $table->index(['category', 'type']);
});
```

### **Caching Strategies**

```php
// Model caching
class Company extends Model
{
    public function getActiveStoresAttribute()
    {
        return Cache::remember(
            "company_{$this->id}_active_stores",
            3600,
            fn() => $this->stores()->where('is_active', true)->get()
        );
    }
}

// Route caching
php artisan route:cache

// View caching
php artisan view:cache

// Configuration caching
php artisan config:cache
```

### **Memory Management**

```php
// Large file processing
function processLargeCsv($filePath)
{
    $handle = fopen($filePath, 'r');
    
    while (($row = fgetcsv($handle)) !== false) {
        // Process row
        
        // Periodic memory cleanup
        if (memory_get_usage() > 50 * 1024 * 1024) { // 50MB
            gc_collect_cycles();
        }
    }
    
    fclose($handle);
}
```

---

## 🔐 **Security Best Practices**

### **Input Validation**

```php
// Form request validation
class ImportCsvRequest extends FormRequest
{
    public function rules()
    {
        return [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'store_id' => 'required|exists:stores,id',
        ];
    }
    
    protected function prepareForValidation()
    {
        // Ensure store belongs to user's company
        $this->merge([
            'store_id' => $this->route('store')->id
        ]);
    }
}
```

### **Authorization**

```php
// Policy-based authorization
class StorePolicy
{
    public function view(User $user, Store $store)
    {
        return $user->company_id === $store->company_id;
    }
    
    public function update(User $user, Store $store)
    {
        return $user->company_id === $store->company_id 
            && $user->can('manage_stores');
    }
}

// Usage in controllers
public function show(Store $store)
{
    $this->authorize('view', $store);
    return view('stores.show', compact('store'));
}
```

### **Data Encryption**

```php
// Automatic encryption for sensitive fields
class Store extends Model
{
    protected $casts = [
        'shopify_access_token' => 'encrypted',
        'api_credentials' => 'encrypted:array',
    ];
}
```

---

## 📚 **Code Style & Standards**

### **PSR-12 Compliance**

```bash
# Check code style
./vendor/bin/pint --dry-run

# Fix code style
./vendor/bin/pint

# Fix only changed files
./vendor/bin/pint --dirty
```

### **Naming Conventions**

```php
// Classes: PascalCase
class ImportOrchestrator {}

// Methods: camelCase
public function processImport() {}

// Variables: camelCase
$importResult = new ImportResult();

// Constants: SCREAMING_SNAKE_CASE
const FORMAT_MERCURY = 'mercury';

// Database tables: snake_case
Schema::create('import_batches', function (Blueprint $table) {});

// Database columns: snake_case
$table->string('batch_id');
$table->timestamp('created_at');
```

### **Documentation Standards**

```php
/**
 * Process CSV import with comprehensive error handling
 *
 * @param ImportBatch $batch The import batch to process
 * @param string $csvData Raw CSV data to process
 * @return ImportResult Result object with success/failure status
 * @throws ImportException When critical import error occurs
 */
public function process(ImportBatch $batch, string $csvData): ImportResult
{
    // Implementation
}
```

---

## 🎯 **Development Tips & Best Practices**

### **1. Multi-Tenancy First**
Always consider company-based data isolation:
```php
// ❌ Wrong
$stores = Store::all();

// ✅ Correct
$stores = auth()->user()->company->stores;
```

### **2. Business Logic in Models**
Keep business logic in models, not controllers:
```php
// ❌ Wrong - in controller
public function calculateProfit(Store $store)
{
    $revenue = $store->transactions()->where('type', 'revenue')->sum('amount');
    $expenses = $store->transactions()->where('type', 'expense')->sum('amount');
    return $revenue - $expenses;
}

// ✅ Correct - in model
class Store extends Model
{
    public function calculateProfit(): float
    {
        return $this->revenue_total - $this->expense_total;
    }
    
    public function getRevenueTotalAttribute(): float
    {
        return $this->transactions()->revenue()->sum('amount');
    }
}
```

### **3. Use Service Classes for Complex Operations**
```php
// Service class for complex business logic
class PartnershipProfitCalculator
{
    public function calculate(Partnership $partnership, Carbon $startDate, Carbon $endDate): float
    {
        $storeProfit = $partnership->store->calculateProfit($startDate, $endDate);
        return $storeProfit * ($partnership->ownership_percentage / 100);
    }
}
```

### **4. Comprehensive Error Handling**
```php
public function importCsv(UploadedFile $file): ImportResult
{
    try {
        DB::beginTransaction();
        
        $result = $this->processFile($file);
        
        if ($result->isSuccess()) {
            DB::commit();
            Log::info('Import successful', ['file' => $file->getClientOriginalName()]);
        } else {
            DB::rollBack();
            Log::warning('Import failed', ['errors' => $result->getErrors()]);
        }
        
        return $result;
        
    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Import exception', ['error' => $e->getMessage()]);
        return ImportResult::failure('Import failed: ' . $e->getMessage());
    }
}
```

### **5. Test-Driven Development**
Write tests before implementing features:
```php
// Test first
public function test_partnership_profit_calculation()
{
    $partnership = Partnership::factory()->create(['ownership_percentage' => 25.0]);
    $profit = $partnership->calculateProfit(now()->subMonth(), now());
    $this->assertEquals(250.0, $profit); // 25% of 1000
}

// Then implement
class Partnership extends Model
{
    public function calculateProfit(Carbon $start, Carbon $end): float
    {
        // Implementation
    }
}
```

---

## 🔧 **IDE Configuration**

### **VS Code Settings**

```json
{
    "php.validate.executablePath": "./vendor/bin/php",
    "phpcs.executablePath": "./vendor/bin/phpcs",
    "phpcs.standard": "PSR12",
    "php-cs-fixer.executablePath": "./vendor/bin/pint",
    "emmet.includeLanguages": {
        "blade": "html"
    },
    "files.associations": {
        "*.blade.php": "blade"
    }
}
```

### **PhpStorm Configuration**

1. **Code Style**: Settings → PHP → Code Style → Set from PSR-12
2. **Blade Support**: Enable Laravel plugin
3. **Database**: Connect to development database
4. **Composer Scripts**: Enable Composer integration

---

## 📖 **Additional Resources**

### **Documentation**
- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [Eloquent Relationships](https://laravel.com/docs/eloquent-relationships)
- [Laravel Testing](https://laravel.com/docs/testing)

### **Community**
- [Laravel Discord](https://discord.gg/laravel)
- [Filament Discord](https://discord.gg/filamentphp)
- [Laracasts](https://laracasts.com)

### **Tools**
- [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar)
- [Laravel Telescope](https://laravel.com/docs/telescope)
- [Laravel Horizon](https://laravel.com/docs/horizon)

---

**🎉 Happy Coding! This guide covers everything you need to develop, test, and maintain the KING SaaS platform effectively.** 