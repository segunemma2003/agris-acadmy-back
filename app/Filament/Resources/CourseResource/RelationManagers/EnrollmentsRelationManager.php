<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use App\Jobs\GenerateCertificateJob;
use App\Models\CertificateTemplate;
use App\Models\Enrollment;
use App\Services\NotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;

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
                Tables\Filters\Filter::make('progress_percentage')
                    ->label('Progress (%)')
                    ->form([
                        Forms\Components\TextInput::make('min_progress')
                            ->label('Min %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->placeholder('0'),
                        Forms\Components\TextInput::make('max_progress')
                            ->label('Max %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->placeholder('100'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['min_progress'] !== null && $data['min_progress'] !== '',
                                fn ($q) => $q->where('progress_percentage', '>=', $data['min_progress'])
                            )
                            ->when(
                                $data['max_progress'] !== null && $data['max_progress'] !== '',
                                fn ($q) => $q->where('progress_percentage', '<=', $data['max_progress'])
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (($data['min_progress'] ?? '') !== '') {
                            $indicators[] = 'Progress ≥ ' . $data['min_progress'] . '%';
                        }
                        if (($data['max_progress'] ?? '') !== '') {
                            $indicators[] = 'Progress ≤ ' . $data['max_progress'] . '%';
                        }
                        return $indicators;
                    }),
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
                        // Queued so large batches never block the request; requires a
                        // running queue worker (supervisor handles this in production).
                        GenerateCertificateJob::dispatch(
                            $record->id,
                            (int) $data['certificate_template_id'],
                            $data['recipient_name']
                        );

                        Notification::make()
                            ->title('Certificate queued')
                            ->body("Generating certificate for {$record->user->name}. It'll appear shortly.")
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
                            $templateId = (int) $data['certificate_template_id'];
                            $adminId = Auth::id();
                            $courseTitle = $this->getOwnerRecord()->title;

                            // Build one queued job per selectable participant.
                            $jobs = [];
                            $skipped = 0;
                            foreach ($records as $enrollment) {
                                // Skip rows with no linked user — a certificate can't be built without one.
                                if (!$enrollment->user_id) {
                                    $skipped++;
                                    continue;
                                }
                                $jobs[] = new GenerateCertificateJob($enrollment->id, $templateId);
                            }

                            if (empty($jobs)) {
                                Notification::make()
                                    ->title('Nothing to generate')
                                    ->body("No selected rows had a linked participant account.")
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Dispatch as a batch so we get a single completion callback when
                            // the whole selection has finished processing in the background.
                            Bus::batch($jobs)
                                ->name("Certificates: {$courseTitle}")
                                ->finally(function (Batch $batch) use ($adminId, $courseTitle) {
                                    if (!$adminId) {
                                        return;
                                    }
                                    $done = $batch->processedJobs() - $batch->failedJobs;
                                    $admin = \App\Models\User::find($adminId);
                                    if (!$admin) {
                                        return;
                                    }

                                    NotificationService::create(
                                        $admin,
                                        'certificate_batch',
                                        'Certificate batch complete',
                                        "{$done} of {$batch->totalJobs} certificate(s) generated for '{$courseTitle}'."
                                            . ($batch->failedJobs > 0 ? " {$batch->failedJobs} failed — check the logs." : ''),
                                        'course',
                                        null,
                                        [
                                            'course_title' => $courseTitle,
                                            'total' => $batch->totalJobs,
                                            'generated' => $done,
                                            'failed' => $batch->failedJobs,
                                        ]
                                    );
                                })
                                ->dispatch();

                            $count = count($jobs);
                            $body = "{$count} certificate(s) queued for '{$courseTitle}'. You'll get a dashboard notification when the batch finishes.";
                            if ($skipped > 0) {
                                $body .= " {$skipped} skipped (no linked participant account).";
                            }

                            Notification::make()
                                ->title('Certificate batch queued')
                                ->body($body)
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
