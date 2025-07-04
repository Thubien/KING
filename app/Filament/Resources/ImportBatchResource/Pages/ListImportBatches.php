<?php

namespace App\Filament\Resources\ImportBatchResource\Pages;

use App\Filament\Resources\ImportBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListImportBatches extends ListRecords
{
    protected static string $resource = ImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Import Transactions')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->url('/admin/import-transactions'),
        ];
    }
}
