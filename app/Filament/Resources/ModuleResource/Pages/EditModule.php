<?php

namespace App\Filament\Resources\ModuleResource\Pages;

use App\Filament\Resources\ModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModule extends EditRecord
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to course')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => \App\Filament\Resources\CourseResource::getUrl('edit', ['record' => $this->record->course_id])),
            Actions\DeleteAction::make()
                ->successRedirectUrl(fn () => \App\Filament\Resources\CourseResource::getUrl('edit', ['record' => $this->record->course_id])),
        ];
    }
}
