<?php

namespace App\Filament\Resources\ImportBatchResource\Pages;

use App\Filament\Resources\ImportBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewImportBatch extends ViewRecord
{
    protected static string $resource = ImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'failed'),

            Actions\Action::make('reprocess')
                ->label('Reprocess Import')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->canBeReprocessed())
                ->requiresConfirmation()
                ->modalHeading('Reprocess Import')
                ->modalDescription('Are you sure you want to reprocess this import? This will create new transactions from the original file.')
                ->action(function () {
                    $orchestrator = app(\App\Services\Import\ImportOrchestrator::class);
                    $result = $orchestrator->reprocessImport($this->record);

                    if ($result->success) {
                        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));

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

            Actions\Action::make('download')
                ->label('Download File')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => ! empty($this->record->file_path))
                ->url(fn () => route('import.download', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
