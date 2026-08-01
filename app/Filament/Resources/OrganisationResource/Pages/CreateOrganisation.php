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
     * Host organisations have no public self-registration for admin-created
     * accounts, so creating an Organisation here also creates its linked User
     * (role: organisation) from the contact fields. Partners (funders) are
     * created separately via Users → Partner (funder).
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
