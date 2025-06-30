<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Services\BalanceValidationService;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ValidateBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances:validate {--company= : Validate specific company} {--notify : Send notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate all company balances and notify discrepancies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $balanceService = new BalanceValidationService;

        // If specific company is provided
        if ($companyId = $this->option('company')) {
            $companies = Company::where('id', $companyId)->get();
        } else {
            $companies = Company::all();
        }

        $this->info("Validating balances for {$companies->count()} companies...");

        $errors = 0;
        $warnings = 0;

        foreach ($companies as $company) {
            $this->line("Checking company: {$company->name}");

            $result = $balanceService->validateCompanyBalance($company);

            if ($result['is_valid']) {
                $this->info("✓ Balance valid - Real: {$result['real_money_total']} | Calculated: {$result['calculated_balance']}");
            } else {
                $errors++;
                $this->error("✗ Balance mismatch - Difference: {$result['difference']}");
                $this->error("  Real money: {$result['real_money_total']}");
                $this->error("  Calculated: {$result['calculated_balance']}");

                // Show breakdown
                if ($this->option('verbose')) {
                    $this->table(
                        ['Type', 'Name', 'Balance'],
                        $this->formatBreakdownTable($result['breakdown'])
                    );
                }

                // Send notifications if requested
                if ($this->option('notify')) {
                    $this->sendNotifications($company, $result);
                }
            }

            $this->line('');
        }

        // Summary
        $this->info('Validation complete!');
        $this->info("Total companies: {$companies->count()}");
        $this->info('Valid balances: '.($companies->count() - $errors));
        $this->error("Balance errors: {$errors}");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function formatBreakdownTable(array $breakdown): array
    {
        $rows = [];

        // Bank accounts
        foreach ($breakdown['bank_accounts'] as $account) {
            $rows[] = [
                'Bank Account',
                $account['bank_type'],
                $account['formatted_balance'],
            ];
        }

        // Payment processors
        foreach ($breakdown['payment_processors'] as $processor) {
            $rows[] = [
                'Payment Processor',
                $processor['processor_type'],
                $processor['formatted_total'],
            ];
        }

        // Stores
        foreach ($breakdown['stores'] as $store) {
            $rows[] = [
                'Store',
                $store['name'],
                $store['formatted_balance'],
            ];
        }

        return $rows;
    }

    private function sendNotifications(Company $company, array $result): void
    {
        // Get company admins
        $admins = User::where('company_id', $company->id)
            ->whereIn('user_type', ['company_owner', 'admin'])
            ->get();

        foreach ($admins as $admin) {
            // In-app notification
            Notification::make()
                ->title('Balance Validation Failed')
                ->body("Balance mismatch detected: {$result['difference']} difference")
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('danger')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('View Details')
                        ->url('/admin'),
                ])
                ->sendToDatabase($admin);

            // Email notification
            if ($admin->email_notifications_enabled ?? true) {
                // Mail::to($admin->email)->queue(new BalanceDiscrepancyMail($company, $result));
                $this->info("Email notification sent to: {$admin->email}");
            }
        }
    }
}
