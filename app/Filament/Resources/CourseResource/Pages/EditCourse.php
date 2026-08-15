<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use App\Mail\CourseCompletionMail;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected ?array $tutors = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('transferOwnership')
                ->label('Transfer ownership')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Select::make('tutor_id')
                        ->label('New primary tutor')
                        ->options(
                            fn () => \App\Models\User::query()
                                ->where('role', 'tutor')
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->required()
                        ->searchable(),
                    \Filament\Forms\Components\Toggle::make('clear_co_tutors')
                        ->label('Remove all co-tutors')
                        ->default(false),
                ])
                ->requiresConfirmation()
                ->modalHeading('Transfer course ownership')
                ->action(function (array $data) {
                    $newTutorId = (int) $data['tutor_id'];
                    if ($newTutorId === (int) $this->record->tutor_id) {
                        \Filament\Notifications\Notification::make()
                            ->title('Already owned by this tutor')
                            ->warning()
                            ->send();

                        return;
                    }

                    $this->record->transferOwnershipTo(
                        $newTutorId,
                        (bool) ($data['clear_co_tutors'] ?? false)
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('Ownership transferred')
                        ->success()
                        ->send();

                    $this->refreshFormData(['tutor_id']);
                }),
            Actions\Action::make('notify_course_finished')
                ->label('Email all participants – course finished')
                ->icon('heroicon-o-envelope')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Send course completion email to all participants')
                ->modalDescription('This will queue a "course finished" email to every enrolled participant for this course. Only use when the course has officially ended.')
                ->modalSubmitActionLabel('Send to all')
                ->action(function () {
                    $course = $this->record;
                    $enrollments = $course->enrollments()->with('user')->get();
                    $sent = 0;
                    foreach ($enrollments as $enrollment) {
                        if ($enrollment->user && $enrollment->user->email) {
                            try {
                                Mail::to($enrollment->user->email)->queue(new CourseCompletionMail(
                                    $enrollment->user,
                                    $course,
                                    $enrollment
                                ));
                                $sent++;
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to queue course completion email', [
                                    'enrollment_id' => $enrollment->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                    \Filament\Notifications\Notification::make()
                        ->title('Emails queued')
                        ->body($sent === 0
                            ? 'No participants with email to notify.'
                            : "Course completion email queued for {$sent} participant(s).")
                        ->success()
                        ->send();
                }),
            ...parent::getHeaderActions(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract tutors if provided
        $tutors = $data['tutors'] ?? [];
        unset($data['tutors']);
        
        // Store tutors for afterSave
        $this->tutors = $tutors;
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Sync tutors after course is saved
        if (isset($this->tutors)) {
            $this->record->tutors()->sync($this->tutors);
        }
    }
}

