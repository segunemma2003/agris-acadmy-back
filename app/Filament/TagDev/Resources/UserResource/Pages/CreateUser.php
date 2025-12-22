<?php

namespace App\Filament\TagDev\Resources\UserResource\Pages;

use App\Filament\TagDev\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
