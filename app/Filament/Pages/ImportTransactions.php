<?php

namespace App\Filament\Pages;

use App\Models\BankAccount;
use App\Models\Store;
use App\Services\Import\ImportOrchestrator;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportTransactions extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Import Transactions';

    protected static ?string $title = 'Import Transactions from CSV/Excel';

    protected static ?string $navigationGroup = 'System & Analytics';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user?->isOwner() || $user?->isSuperAdmin(); // Show for owners and admins
    }

    protected static string $view = 'filament.pages.import-transactions';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Upload CSV File')
                    ->description('Upload your bank statement or transaction export file')
                    ->schema([
                        FileUpload::make('csv_file')
                            ->label('CSV File')
                            ->acceptedFileTypes(['text/csv', 'application/csv', 'text/plain'])
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->disk('local')
                            ->directory('imports')
                            ->preserveFilenames()
                            ->helperText('Supported formats: Payoneer, Mercury Bank, Stripe, and standard bank statements'),

                        Select::make('source_type')
                            ->label('Source Type (Optional)')
                            ->options([
                                'auto' => 'Auto-detect (Recommended)',
                                'payoneer' => 'Payoneer',
                                'mercury' => 'Mercury Bank',
                                'stripe' => 'Stripe',
                                'bank' => 'Generic Bank Statement',
                                'other' => 'Other',
                            ])
                            ->default('auto')
                            ->helperText('Leave as auto-detect for automatic format detection'),
                    ]),

                Section::make('Import Settings')
                    ->schema([
                        Select::make('default_store_id')
                            ->label('Default Store')
                            ->options(
                                Store::where('company_id', Auth::user()->company_id)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->helperText('Transactions will be imported as pending and can be assigned later'),

                        Select::make('bank_account_id')
                            ->label('Bank Account')
                            ->options(
                                BankAccount::where('company_id', Auth::user()->company_id)
                                    ->pluck('account_name', 'id')
                            )
                            ->searchable()
                            ->helperText('Select the bank account these transactions belong to'),

                        Toggle::make('skip_duplicates')
                            ->label('Skip Duplicate Transactions')
                            ->default(true)
                            ->helperText('Skip transactions that already exist in the system'),

                        Toggle::make('auto_categorize')
                            ->label('Enable Smart Suggestions')
                            ->default(true)
                            ->helperText('Use pattern matching to suggest categories (requires manual confirmation)'),

                        Textarea::make('notes')
                            ->label('Import Notes')
                            ->rows(2)
                            ->placeholder('Any notes about this import batch...'),
                    ]),
            ])
            ->statePath('data');
    }

    public function import(): void
    {
        $data = $this->form->getState();

        try {
            // Get the uploaded file
            /** @var TemporaryUploadedFile $uploadedFile */
            $uploadedFile = $data['csv_file'];

            if (! $uploadedFile) {
                throw new \Exception('No file uploaded');
            }

            // Get the actual file path
            $filePath = storage_path('app/public/'.$uploadedFile->store('imports', 'public'));

            // Create import orchestrator
            $orchestrator = app(ImportOrchestrator::class);

            // Prepare import options
            $options = [
                'source_type' => $data['source_type'] === 'auto' ? null : $data['source_type'],
                'default_store_id' => $data['default_store_id'] ?? null,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'skip_duplicates' => $data['skip_duplicates'] ?? true,
                'auto_categorize' => $data['auto_categorize'] ?? true,
                'notes' => $data['notes'] ?? null,
                'user_id' => Auth::id(),
            ];

            // Start the import
            $result = $orchestrator->importFromCsv($filePath, $options);

            if ($result->success) {
                Notification::make()
                    ->title('Import started successfully')
                    ->body("Processing {$result->totalRecords} transactions. You can monitor progress in Import History.")
                    ->success()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('View Import')
                            ->url("/admin/import-batches/{$result->importBatch->id}"),
                    ])
                    ->send();

                // Clear the form
                $this->form->fill([
                    'csv_file' => null,
                    'source_type' => 'auto',
                    'skip_duplicates' => true,
                    'auto_categorize' => true,
                ]);

                // Redirect to import history
                $this->redirect('/admin/import-batches');
            } else {
                Notification::make()
                    ->title('Import failed')
                    ->body($result->errorMessage ?? 'Unknown error occurred')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Import error')
                ->body($e->getMessage())
                ->danger()
                ->send();

            \Log::error('Import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('import')
                ->label('Start Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->submit('import')
                ->color('primary'),
        ];
    }
}
