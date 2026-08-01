<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Organisation;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @var array<string, mixed> */
    protected array $partnerData = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $organisation = $this->record->organisation;

        if ($organisation) {
            $data['organisation_name'] = $organisation->name;
            $data['organisation_sector'] = $organisation->sector;
            $data['organisation_state'] = $organisation->state;
            $data['organisation_is_approved'] = $organisation->is_approved;
            $data['dashboard_permissions'] = $organisation->dashboard_permissions ?? [];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->partnerData = [
            'organisation_name' => $this->data['organisation_name'] ?? null,
            'organisation_sector' => $this->data['organisation_sector'] ?? null,
            'organisation_state' => $this->data['organisation_state'] ?? null,
            'organisation_is_approved' => (bool) ($this->data['organisation_is_approved'] ?? false),
            'dashboard_permissions' => $this->data['dashboard_permissions'] ?? [],
        ];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $user = parent::handleRecordUpdate($record, $data);

        if ($user->role === 'organisation') {
            $approved = $this->partnerData['organisation_is_approved'] ?? false;
            $payload = [
                'name' => $this->partnerData['organisation_name'] ?: $user->name,
                'sector' => $this->partnerData['organisation_sector'] ?: null,
                'state' => $this->partnerData['organisation_state'] ?: null,
                'is_approved' => $approved,
                'dashboard_permissions' => $this->partnerData['dashboard_permissions'] ?: [],
            ];

            if ($approved) {
                $payload['approved_at'] = $user->organisation?->approved_at ?? now();
                $payload['approved_by'] = $user->organisation?->approved_by ?? Auth::id();
            } else {
                $payload['approved_at'] = null;
                $payload['approved_by'] = null;
            }

            Organisation::updateOrCreate(
                ['user_id' => $user->id],
                $payload,
            );
        }

        return $user;
    }
}
