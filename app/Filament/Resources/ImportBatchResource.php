<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportBatchResource\Pages;
use App\Models\ImportBatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImportBatchResource extends Resource
{
    protected static ?string $model = ImportBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Import History';

    protected static ?string $modelLabel = 'Import Batch';

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hide from navigation
    }

    // Enable create action that redirects to import page
    public static function canCreate(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Import Details')
                    ->schema([
                        Forms\Components\TextInput::make('batch_id')
                            ->label('Batch ID')
                            ->disabled(),

                        Forms\Components\Select::make('import_type')
                            ->label('Import Type')
                            ->options([
                                'csv' => 'CSV File',
                                'shopify' => 'Shopify',
                                'stripe' => 'Stripe',
                                'paypal' => 'PayPal',
                                'manual' => 'Manual Entry',
                                'api' => 'API Import',
                            ])
                            ->disabled(),

                        Forms\Components\Select::make('source_type')
                            ->label('Source')
                            ->options([
                                'payoneer' => 'Payoneer',
                                'mercury' => 'Mercury Bank',
                                'stripe' => 'Stripe',
                                'shopify_payments' => 'Shopify Payments',
                                'bank' => 'Bank Statement',
                                'other' => 'Other',
                            ])
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('File Information')
                    ->schema([
                        Forms\Components\TextInput::make('original_filename')
                            ->label('Original Filename')
                            ->disabled(),

                        Forms\Components\TextInput::make('formatted_file_size')
                            ->label('File Size')
                            ->disabled(),

                        Forms\Components\TextInput::make('mime_type')
                            ->label('File Type')
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => ! empty($record->original_filename)),

                Forms\Components\Section::make('Processing Results')
                    ->schema([
                        Forms\Components\TextInput::make('total_records')
                            ->label('Total Records')
                            ->disabled(),

                        Forms\Components\TextInput::make('successful_records')
                            ->label('Successful')
                            ->disabled(),

                        Forms\Components\TextInput::make('failed_records')
                            ->label('Failed')
                            ->disabled(),

                        Forms\Components\TextInput::make('duplicate_records')
                            ->label('Duplicates')
                            ->disabled(),

                        Forms\Components\TextInput::make('progress_percentage')
                            ->label('Progress')
                            ->suffix('%')
                            ->disabled(),

                        Forms\Components\TextInput::make('success_rate')
                            ->label('Success Rate')
                            ->suffix('%')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),

                        Forms\Components\Textarea::make('error_message')
                            ->label('Error Message')
                            ->visible(fn ($record) => ! empty($record->error_message))
                            ->disabled()
                            ->rows(2),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('batch_id')
                    ->label('Batch ID')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('import_type')
                    ->label('Type')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'csv' => 'CSV',
                            'shopify' => 'Shopify',
                            'stripe' => 'Stripe',
                            'paypal' => 'PayPal',
                            'manual' => 'Manual',
                            'api' => 'API',
                            default => ucfirst($state)
                        };
                    }),

                Tables\Columns\TextColumn::make('source_type')
                    ->label('Source')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'payoneer' => 'Payoneer',
                            'mercury' => 'Mercury',
                            'stripe' => 'Stripe',
                            'shopify_payments' => 'Shopify Pay',
                            'bank' => 'Bank',
                            'other' => 'Other',
                            default => ucfirst($state ?? 'Unknown')
                        };
                    }),

                Tables\Columns\TextColumn::make('original_filename')
                    ->label('File')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record ? $record->original_filename : ''),

                Tables\Columns\TextColumn::make('total_records')
                    ->label('Records')
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->alignRight()
                    ->formatStateUsing(fn ($record) => $record ? $record->getProgressPercentage().'%' : '0%')
                    ->color(fn ($record) => match (true) {
                        $record->getProgressPercentage() == 100 => 'success',
                        $record->getProgressPercentage() > 50 => 'warning',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('success_rate')
                    ->label('Success Rate')
                    ->alignRight()
                    ->formatStateUsing(fn ($record) => $record ? $record->getSuccessRate().'%' : '0%')
                    ->color(fn ($record) => match (true) {
                        $record->getSuccessRate() >= 90 => 'success',
                        $record->getSuccessRate() >= 70 => 'warning',
                        default => 'danger'
                    }),

                Tables\Columns\TextColumn::make('duration_formatted')
                    ->label('Duration')
                    ->alignRight()
                    ->formatStateUsing(fn ($record) => $record ? $record->getDurationFormatted() : 'N/A'),

                Tables\Columns\TextColumn::make('initiator.name')
                    ->label('Initiated By')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('requires_review')
                    ->label('Review')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('import_type')
                    ->label('Import Type')
                    ->options([
                        'csv' => 'CSV File',
                        'shopify' => 'Shopify',
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'manual' => 'Manual',
                        'api' => 'API',
                    ]),

                Tables\Filters\SelectFilter::make('source_type')
                    ->label('Source')
                    ->options([
                        'payoneer' => 'Payoneer',
                        'mercury' => 'Mercury',
                        'stripe' => 'Stripe',
                        'shopify_payments' => 'Shopify Payments',
                        'bank' => 'Bank',
                        'other' => 'Other',
                    ]),

                Tables\Filters\Filter::make('requires_review')
                    ->label('Requires Review')
                    ->query(fn (Builder $query) => $query->where('requires_review', true)),

                Tables\Filters\Filter::make('has_errors')
                    ->label('Has Errors')
                    ->query(fn (Builder $query) => $query->where(function ($q) {
                        $q->whereNotNull('error_message')
                            ->orWhereNotNull('errors');
                    })),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record && $record->status === 'failed'),

                Tables\Actions\Action::make('reprocess')
                    ->label('Reprocess')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => $record && $record->canBeReprocessed())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $orchestrator = app(\App\Services\Import\ImportOrchestrator::class);
                        $result = $orchestrator->reprocessImport($record);

                        if ($result->success) {
                            \Filament\Notifications\Notification::make()
                                ->title('Import reprocessed successfully')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Reprocessing failed')
                                ->body($result->errorMessage)
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds for live updates
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImportBatches::route('/'),
            'create' => Pages\CreateImportBatch::route('/create'),
            'view' => Pages\ViewImportBatch::route('/{record}'),
            'edit' => Pages\EditImportBatch::route('/{record}/edit'),
        ];
    }
}
