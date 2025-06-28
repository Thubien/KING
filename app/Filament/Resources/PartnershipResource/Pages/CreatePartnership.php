<?php

namespace App\Filament\Resources\PartnershipResource\Pages;

use App\Filament\Resources\PartnershipResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePartnership extends CreateRecord
{
    protected static string $resource = PartnershipResource::class;

    protected function afterCreate(): void
    {
        $partnership = $this->record;

        // If partner_email is provided but no user_id, send invitation
        if ($partnership->partner_email && !$partnership->user_id) {
            try {
                $partnership->generateInvitationToken();
                $partnership->sendInvitationEmail();

                Notification::make()
                    ->title('Partnership created and invitation sent!')
                    ->body("Invitation email sent to {$partnership->partner_email}")
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Partnership created but invitation failed')
                    ->body("Error: {$e->getMessage()}")
                    ->warning()
                    ->send();
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If partner_email is provided but no user_id, set status to PENDING_INVITATION
        if (!empty($data['partner_email']) && empty($data['user_id'])) {
            $data['status'] = 'PENDING_INVITATION';
        }

        return $data;
    }
}
