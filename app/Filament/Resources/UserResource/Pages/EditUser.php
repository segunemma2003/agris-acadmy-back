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
    protected array $hostData = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $organisation = $this->record->organisation;

        if ($organisation) {
            $data['organisation_name'] = $organisation->name;
            $data['organisation_sector'] = $organisation->sector;
            $data['organisation_state'] = $organisation->state;
            $data['organisation_is_approved'] = $organisation->is_approved;
        }

        $data['dashboard_permissions'] = $this->record->dashboard_permissions ?? [];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['role'] ?? null) === 'partner') {
            $data['dashboard_permissions'] = $this->data['dashboard_permissions'] ?? [];
        } else {
            $data['dashboard_permissions'] = null;
        }

        $this->hostData = [
            'organisation_name' => $this->data['organisation_name'] ?? null,
            'organisation_sector' => $this->data['organisation_sector'] ?? null,
            'organisation_state' => $this->data['organisation_state'] ?? null,
            'organisation_is_approved' => (bool) ($this->data['organisation_is_approved'] ?? false),
        ];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $user = parent::handleRecordUpdate($record, $data);

        if ($user->role === 'organisation') {
            $approved = $this->hostData['organisation_is_approved'] ?? false;
            $payload = [
                'name' => $this->hostData['organisation_name'] ?: $user->name,
                'sector' => $this->hostData['organisation_sector'] ?: null,
                'state' => $this->hostData['organisation_state'] ?: null,
                'is_approved' => $approved,
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
