<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Organisation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** @var array<string, mixed> */
    protected array $hostData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function handleRecordCreation(array $data): Model
    {
        $user = parent::handleRecordCreation($data);

        if ($user->role === 'organisation') {
            $approved = $this->hostData['organisation_is_approved'] ?? false;

            Organisation::create([
                'user_id' => $user->id,
                'name' => $this->hostData['organisation_name'] ?: $user->name,
                'sector' => $this->hostData['organisation_sector'] ?: null,
                'state' => $this->hostData['organisation_state'] ?: null,
                'is_approved' => $approved,
                'approved_at' => $approved ? now() : null,
                'approved_by' => $approved ? Auth::id() : null,
            ]);
        }

        return $user;
    }
}
