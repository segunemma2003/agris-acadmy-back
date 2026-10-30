<?php

namespace App\Filament\Resources\ModuleTestResource\Pages;

use App\Filament\Resources\ModuleTestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModuleTest extends EditRecord
{
    protected static string $resource = ModuleTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to module')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => \App\Filament\Resources\ModuleResource::getUrl('edit', ['record' => $this->record->module_id])),
            Actions\DeleteAction::make()
                ->successRedirectUrl(fn () => \App\Filament\Resources\ModuleResource::getUrl('edit', ['record' => $this->record->module_id])),
        ];
    }
}
