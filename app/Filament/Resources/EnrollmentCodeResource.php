<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentCodeResource\Pages;
use App\Models\EnrollmentCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\EnrollmentCodeMail;

class EnrollmentCodeResource extends Resource
{
    protected static ?string $model = EnrollmentCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Course Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Enrollment Code')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->relationship('course', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('tutor_id')
                            ->relationship('tutor', 'name', fn ($query) => $query->where('role', 'tutor'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->default(fn () => EnrollmentCode::generateCode())
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->label('Email Address')
                            ->helperText('Email to send the enrollment code to'),
                        Forms\Components\DateTimePicker::make('expires_at'),
                        Forms\Components\Toggle::make('is_used')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('course.title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tutor.name')
                    ->label('Tutor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Assigned To')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_used')
                    ->boolean(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('used_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->relationship('course', 'title'),
                Tables\Filters\TernaryFilter::make('is_used'),
            ])
            ->actions([
                Tables\Actions\Action::make('send_email')
                    ->label('Send Email')
                    ->icon('heroicon-o-envelope')
                    ->visible(fn ($record) => $record->email && !$record->is_used)
                    ->action(function (EnrollmentCode $record) {
                        try {
                            Mail::to($record->email)->queue(new \App\Mail\EnrollmentCodeMail($record));
                            Notification::make()
                                ->title('Email sent successfully!')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to send email')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_send_to_students')
                        ->label('Send Codes to Selected Students')
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
                            Forms\Components\Select::make('student_ids')
                                ->label('Select Students')
                                ->options(function () {
                                    return \App\Models\User::where('role', 'student')
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->required()
                                ->searchable()
                                ->preload()
                                ->helperText('Select students to send enrollment codes to'),
                            Forms\Components\DateTimePicker::make('expires_at')
                                ->label('Expiration Date (Optional)')
                                ->helperText('Leave empty for no expiration'),
                        ])
                        ->action(function (array $data) {
                            $studentIds = $data['student_ids'] ?? [];
                            
                            if (empty($studentIds)) {
                                Notification::make()
                                    ->title('No students selected')
                                    ->body('Please select at least one student.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $students = \App\Models\User::whereIn('id', $studentIds)
                                ->where('role', 'student')
                                ->get();

                            if ($students->isEmpty()) {
                                Notification::make()
                                    ->title('No valid students found')
                                    ->body('Selected users are not students.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $created = 0;
                            $sent = 0;
                            $errors = [];

                            foreach ($students as $student) {
                                try {
                                    // Check if code already exists for this student and course
                                    $existingCode = EnrollmentCode::where('course_id', $data['course_id'])
                                        ->where('email', $student->email)
                                        ->where('is_used', false)
                                        ->first();

                                    if ($existingCode) {
                                        // Use existing code
                                        $code = $existingCode;
                                    } else {
                                        // Create new code
                                        $code = EnrollmentCode::create([
                                            'course_id' => $data['course_id'],
                                            'tutor_id' => $data['tutor_id'],
                                            'user_id' => $student->id,
                                            'email' => $student->email,
                                            'code' => EnrollmentCode::generateCode(),
                                            'expires_at' => $data['expires_at'] ?? null,
                                            'is_used' => false,
                                        ]);
                                        $created++;
                                    }

                                    // Send email
                                    try {
                                        Mail::to($student->email)->queue(new \App\Mail\EnrollmentCodeMail($code));
                                        $sent++;
                                    } catch (\Exception $e) {
                                        $errors[] = "Failed to send email to {$student->email}: " . $e->getMessage();
                                        \Log::error('Failed to send enrollment code email to ' . $student->email . ': ' . $e->getMessage());
                                    }
                                } catch (\Exception $e) {
                                    $errors[] = "Failed to create code for {$student->name}: " . $e->getMessage();
                                    \Log::error('Failed to create enrollment code: ' . $e->getMessage());
                                }
                            }

                            $message = "Created {$created} enrollment code(s) and sent {$sent} email(s)";
                            if (!empty($errors)) {
                                $message .= ". " . count($errors) . " error(s) occurred.";
                            }

                            Notification::make()
                                ->title('Bulk enrollment codes sent')
                                ->body($message)
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('bulk_create')
                        ->label('Bulk Create & Send (Email List)')
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
                                ->helperText('Enter email addresses separated by commas, new lines, or semicolons')
                                ->required()
                                ->rows(10)
                                ->placeholder('email1@example.com, email2@example.com, email3@example.com'),
                            Forms\Components\DateTimePicker::make('expires_at')
                                ->label('Expiration Date (Optional)')
                                ->helperText('Leave empty for no expiration'),
                        ])
                        ->action(function (array $data) {
                            // Parse emails from textarea
                            $emailText = $data['emails'];
                            $emails = preg_split('/[,\n\r;]+/', $emailText);
                            $emails = array_map('trim', $emails);
                            $emails = array_filter($emails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

                            if (empty($emails)) {
                                Notification::make()
                                    ->title('No valid emails provided')
                                    ->body('Please provide at least one valid email address.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $created = 0;
                            $sent = 0;
                            $errors = [];

                            foreach ($emails as $email) {
                                try {
                                    // Try to find student by email
                                    $student = \App\Models\User::where('email', $email)
                                        ->where('role', 'student')
                                        ->first();

                                    // Check if code already exists for this email and course
                                    $existingCode = EnrollmentCode::where('course_id', $data['course_id'])
                                        ->where('email', $email)
                                        ->where('is_used', false)
                                        ->first();

                                    if ($existingCode) {
                                        // Use existing code
                                        $code = $existingCode;
                                    } else {
                                        // Create new code
                                        $code = EnrollmentCode::create([
                                            'course_id' => $data['course_id'],
                                            'tutor_id' => $data['tutor_id'],
                                            'user_id' => $student ? $student->id : null,
                                            'email' => $email,
                                            'code' => EnrollmentCode::generateCode(),
                                            'expires_at' => $data['expires_at'] ?? null,
                                            'is_used' => false,
                                        ]);
                                        $created++;
                                    }

                                    // Send email
                                    try {
                                        Mail::to($email)->queue(new \App\Mail\EnrollmentCodeMail($code));
                                        $sent++;
                                    } catch (\Exception $e) {
                                        $errors[] = "Failed to send email to {$email}: " . $e->getMessage();
                                        \Log::error('Failed to send enrollment code email to ' . $email . ': ' . $e->getMessage());
                                    }
                                } catch (\Exception $e) {
                                    $errors[] = "Failed to create code for {$email}: " . $e->getMessage();
                                    \Log::error('Failed to create enrollment code: ' . $e->getMessage());
                                }
                            }

                            $message = "Created {$created} enrollment code(s) and sent {$sent} email(s)";
                            if (!empty($errors)) {
                                $message .= ". " . count($errors) . " error(s) occurred.";
                            }

                            Notification::make()
                                ->title('Bulk creation completed')
                                ->body($message)
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollmentCodes::route('/'),
            'create' => Pages\CreateEnrollmentCode::route('/create'),
            'edit' => Pages\EditEnrollmentCode::route('/{record}/edit'),
        ];
    }
}



