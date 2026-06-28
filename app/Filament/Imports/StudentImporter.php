<?php

namespace App\Filament\Imports;

use App\Models\User;
use App\Services\FacilitatorAssignmentService;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;

class StudentImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')->requiredMapping()->rules(['required', 'string', 'max:255']),
            ImportColumn::make('email')->requiredMapping()->rules(['required', 'email', 'max:255']),
            ImportColumn::make('phone')->rules(['nullable', 'string', 'max:20']),
            ImportColumn::make('gender')->rules(['nullable', 'in:male,female,other']),
            ImportColumn::make('state')->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('lga')->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('location')->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('occupation')->rules(['nullable', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?User
    {
        return User::firstOrNew(['email' => $this->data['email']]);
    }

    protected function beforeSave(): void
    {
        if (!$this->record->exists) {
            $this->record->password  = Hash::make(\Str::random(16));
            $this->record->role      = 'student';
            $this->record->is_active = true;
        }
    }

    protected function afterSave(): void
    {
        if ($this->record->wasRecentlyCreated) {
            try {
                app(FacilitatorAssignmentService::class)->assign($this->record);
            } catch (\Throwable $e) {
                \Log::warning('Facilitator assignment failed during import: ' . $e->getMessage());
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Student import completed — ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failed) . ' ' . str('row')->plural($failed) . ' failed.';
        }

        return $body;
    }
}
