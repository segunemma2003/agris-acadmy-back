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
    protected array $partnerData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function handleRecordCreation(array $data): Model
    {
        $user = parent::handleRecordCreation($data);

        if ($user->role === 'organisation') {
            $approved = $this->partnerData['organisation_is_approved'] ?? false;

            Organisation::create([
                'user_id' => $user->id,
                'name' => $this->partnerData['organisation_name'] ?: $user->name,
                'sector' => $this->partnerData['organisation_sector'] ?: null,
                'state' => $this->partnerData['organisation_state'] ?: null,
                'is_approved' => $approved,
                'approved_at' => $approved ? now() : null,
                'approved_by' => $approved ? Auth::id() : null,
                'dashboard_permissions' => $this->partnerData['dashboard_permissions'] ?: [],
            ]);
        }

        return $user;
    }
}
