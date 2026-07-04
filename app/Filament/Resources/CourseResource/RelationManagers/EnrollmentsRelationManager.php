<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use App\Jobs\GenerateCertificateJob;
use App\Models\CertificateTemplate;
use App\Models\Enrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('progress_percentage')
                    ->label('Progress (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('enrollment_code')
            ->modifyQueryUsing(fn ($query) => $query->with(['certificate', 'user']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Participant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable(),
                Tables\Columns\IconColumn::make('certificate.id')
                    ->label('Certificate')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-minus'),
                Tables\Columns\TextColumn::make('enrolled_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_certificate')
                    ->label('View Certificate')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->visible(fn (Enrollment $record) => filled($record->certificate?->file_path))
                    ->url(fn (Enrollment $record) => $record->certificate->file_path)
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('generate_certificate')
                    ->label('Generate Certificate')
                    ->icon('heroicon-o-academic-cap')
                    ->form([
                        Forms\Components\Select::make('certificate_template_id')
                            ->label('Template')
                            ->options(fn () => CertificateTemplate::pluck('name', 'id'))
                            ->default(fn () => $this->defaultTemplateId())
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('recipient_name')
                            ->label('Name on certificate')
                            ->default(fn (Enrollment $record) => $record->user->name)
                            ->helperText("Defaults to the participant's account name. Edit if it needs to be different.")
                            ->required(),
                    ])
                    ->action(function (Enrollment $record, array $data) {
                        GenerateCertificateJob::dispatch(
                            $record->id,
                            $data['certificate_template_id'],
                            $data['recipient_name']
                        );

                        Notification::make()
                            ->title('Certificate queued')
                            ->body("Generating certificate for {$record->user->name}.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('generate_certificates')
                        ->label('Generate Certificates')
                        ->icon('heroicon-o-academic-cap')
                        ->form([
                            Forms\Components\Select::make('certificate_template_id')
                                ->label('Template')
                                ->options(fn () => CertificateTemplate::pluck('name', 'id'))
                                ->default(fn () => $this->defaultTemplateId())
                                ->required()
                                ->searchable()
                                ->helperText('Every selected participant gets their account name printed on this template.'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $enrollment) {
                                GenerateCertificateJob::dispatch(
                                    $enrollment->id,
                                    $data['certificate_template_id']
                                );
                            }

                            Notification::make()
                                ->title('Certificates queued')
                                ->body("Generating {$records->count()} certificate(s) in the background. They'll appear on each participant's account shortly.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('enrolled_at', 'desc');
    }

    /**
     * The course's assigned certificate template, falling back to the global default.
     */
    private function defaultTemplateId(): ?int
    {
        $courseTemplateId = $this->getOwnerRecord()->certificate_template_id;

        return $courseTemplateId ?? CertificateTemplate::where('is_default', true)->value('id');
    }
}
