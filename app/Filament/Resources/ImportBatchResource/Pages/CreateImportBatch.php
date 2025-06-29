<?php

namespace App\Filament\Resources\ImportBatchResource\Pages;

use App\Filament\Resources\ImportBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateImportBatch extends CreateRecord
{
    protected static string $resource = ImportBatchResource::class;
    
    public function mount(): void
    {
        // Redirect to Import Transactions page instead of showing create form
        $this->redirect('/admin/import-transactions');
    }
}
