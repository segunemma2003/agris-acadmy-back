<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Support\ParticipantProfile;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** A learner's courses, with their Part 4 answers (how/why they signed up). */
class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Enrollments (Part 4: your course)';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $label = fn (array $options) => fn ($state) => $state ? ($options[$state] ?? $state) : '—';

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.title')->label('4.1 Course')->wrap(),
                Tables\Columns\TextColumn::make('learning_type')->label('4.2 Type of learning')->formatStateUsing($label(ParticipantProfile::LEARNING_TYPE))->placeholder('—'),
                Tables\Columns\TextColumn::make('learning_mode')->label('4.3 How')->formatStateUsing($label(ParticipantProfile::LEARNING_MODE))->placeholder('—'),
                Tables\Columns\TextColumn::make('heard_from')->label('4.4 Heard via')->formatStateUsing($label(ParticipantProfile::HEARD_FROM))->placeholder('—'),
                Tables\Columns\TextColumn::make('partner_hub')->label('4.5 Partner / hub')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('progress_percentage')->label('Progress')->suffix('%'),
                Tables\Columns\TextColumn::make('completed_at')->dateTime()->placeholder('—'),
            ])
            ->defaultSort('enrolled_at', 'desc');
    }
}
