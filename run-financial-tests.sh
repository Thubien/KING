#!/bin/bash

# Script to run all financial calculation tests

echo "=========================================="
echo "Running Financial Calculation Tests"
echo "=========================================="

# Clear cache and reset environment
php artisan config:clear
php artisan cache:clear

echo ""
echo "1. Running Financial Calculations Tests..."
echo "------------------------------------------"
php artisan test tests/Feature/FinancialCalculationsTest.php --stop-on-failure

echo ""
echo "2. Running Balance Validation Service Tests..."
echo "----------------------------------------------"
php artisan test tests/Unit/BalanceValidationServiceTest.php --stop-on-failure

echo ""
echo "3. Running Currency Conversion Tests..."
echo "---------------------------------------"
php artisan test tests/Feature/CurrencyConversionTest.php --stop-on-failure

echo ""
echo "4. Running All Tests with Coverage Report..."
echo "--------------------------------------------"
php artisan test --coverage --min=70 --stop-on-failure \
    tests/Feature/FinancialCalculationsTest.php \
    tests/Unit/BalanceValidationServiceTest.php \
    tests/Feature/CurrencyConversionTest.php

echo ""
echo "=========================================="
echo "Test Summary"
echo "=========================================="

# Run a quick summary
php artisan test --parallel --stop-on-failure \
    tests/Feature/FinancialCalculationsTest.php \
    tests/Unit/BalanceValidationServiceTest.php \
    tests/Feature/CurrencyConversionTest.php

echo ""
echo "Financial tests completed!"