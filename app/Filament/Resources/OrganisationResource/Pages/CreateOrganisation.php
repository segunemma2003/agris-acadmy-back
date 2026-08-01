<?php

namespace App\Filament\Resources\OrganisationResource\Pages;

use App\Filament\Resources\OrganisationResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class CreateOrganisation extends CreateRecord
{
    protected static string $resource = OrganisationResource::class;

    /**
     * Partner accounts have no public self-registration flow, so creating an
     * Organisation here must also create its linked User (role: organisation)
     * from the contact fields collected on the form.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $user = User::create([
            'name' => $data['contact_name'],
            'email' => $data['contact_email'],
            'password' => Hash::make($data['contact_password']),
            'role' => 'organisation',
            'is_active' => true,
        ]);

        unset($data['contact_name'], $data['contact_email'], $data['contact_password']);
        $data['user_id'] = $user->id;

        return static::getModel()::create($data);
    }
}
