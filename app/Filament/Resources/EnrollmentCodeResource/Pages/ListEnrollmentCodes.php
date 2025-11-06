<?php

namespace App\Filament\Resources\EnrollmentCodeResource\Pages;

use App\Filament\Resources\EnrollmentCodeResource;
use App\Models\EnrollmentCode;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ListEnrollmentCodes extends ListRecords
{
    protected static string $resource = EnrollmentCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('bulk_create')
                ->label('Bulk Create & Send')
                ->icon('heroicon-o-envelope')
                ->form([
                    Forms\Components\Select::make('course_id')
                        ->label('Course')
                        ->relationship('course', 'title')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('tutor_id')
                        ->label('Tutor')
                        ->relationship('tutor', 'name', fn ($query) => $query->where('role', 'tutor'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->default(fn () => Auth::user()->role === 'tutor' ? Auth::id() : null),
                    Forms\Components\Textarea::make('emails')
                        ->label('Email Addresses')
                        ->helperText('Enter email addresses separated by commas or new lines')
                        ->required()
                        ->rows(5),
                    Forms\Components\TextInput::make('count')
                        ->label('Number of Codes per Email')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->maxValue(10)
                        ->required(),
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Expiration Date'),
                ])
                ->action(function (array $data) {
                    $emails = preg_split('/[,\n\r]+/', $data['emails']);
                    $emails = array_map('trim', $emails);
                    $emails = array_filter($emails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

                    if (empty($emails)) {
                        Notification::make()
                            ->title('No valid emails provided')
                            ->danger()
                            ->send();
                        return;
                    }

                    $count = (int) ($data['count'] ?? 1);
                    $created = 0;
                    $sent = 0;

                    foreach ($emails as $email) {
                        for ($i = 0; $i < $count; $i++) {
                            $code = EnrollmentCode::create([
                                'course_id' => $data['course_id'],
                                'tutor_id' => $data['tutor_id'],
                                'email' => $email,
                                'code' => EnrollmentCode::generateCode(),
                                'expires_at' => $data['expires_at'] ?? null,
                                'is_used' => false,
                            ]);
                            $created++;

                            // Send email
                            try {
                                Mail::to($email)->send(new \App\Mail\EnrollmentCodeMail($code));
                                $sent++;
                            } catch (\Exception $e) {
                                // Log error but continue
                                Log::error('Failed to send enrollment code email to ' . $email . ': ' . $e->getMessage());
                            }
                        }
                    }

                    Notification::make()
                        ->title('Bulk creation successful')
                        ->body("Created {$created} enrollment code(s) and sent {$sent} email(s)")
                        ->success()
                        ->send();
                }),
        ];
    }
}

