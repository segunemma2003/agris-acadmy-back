<?php

namespace App\Filament\Tutor\Resources\CourseDiyContentResource\Pages;

use App\Filament\Tutor\Resources\CourseDiyContentResource;
use Filament\Resources\Pages\EditRecord;

class EditCourseDiyContent extends EditRecord
{
    protected static string $resource = CourseDiyContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('review')->label('Review learners\' work')->url(fn () => static::getResource()::submissionsUrl($this->record)),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
