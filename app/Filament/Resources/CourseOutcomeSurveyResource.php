<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseOutcomeSurveyResource\Pages;
use App\Models\CourseOutcomeSurvey;
use App\Support\ParticipantProfile as PP;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** End-of-course checks (participant form Part 5) — read-only for admins. */
class CourseOutcomeSurveyResource extends Resource
{
    protected static ?string $model = CourseOutcomeSurvey::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?string $navigationLabel = 'End-of-course checks';

    protected static ?string $modelLabel = 'End-of-course check';

    public static function form(Form $form): Form
    {
        $yesNo = [1 => 'Yes', 0 => 'No'];

        return $form->schema([
            Forms\Components\Section::make('Learner & course')->schema([
                Forms\Components\Placeholder::make('learner')->content(fn ($record) => $record?->user ? "{$record->user->name} ({$record->user->email})" : '—'),
                Forms\Components\Placeholder::make('course_title')->label('Course')->content(fn ($record) => $record?->course?->title ?? '—'),
                Forms\Components\Select::make('stage')->options(PP::SURVEY_STAGES),
                Forms\Components\Placeholder::make('submitted')->content(fn ($record) => $record?->created_at?->format('j M Y, H:i') ?? '—'),
            ])->columns(2),
            Forms\Components\Section::make('Answers')->schema([
                Forms\Components\Select::make('completed_course')->label('5.1 Completed the course')->options($yesNo),
                Forms\Components\Select::make('work_change')->label('5.3 What has changed in their work')->options(PP::WORK_CHANGE),
                Forms\Components\Select::make('started_enterprise')->label('5.4 Started an agri-enterprise')->options($yesNo),
                Forms\Components\Select::make('feels_respected')->label('5.5 Feels heard and respected at work')->options(PP::YES_NO_PREFER),
                Forms\Components\Select::make('income_band')->label('Monthly earnings now')->options(PP::INCOME + PP::INCOME_LEGACY),
                Forms\Components\Select::make('rating')->label('5.10 Course rating')->options(PP::RATING),
                Forms\Components\Select::make('used_learning')->label('5.12 Used what they learnt')->options(PP::USED_LEARNING),
                Forms\Components\Select::make('would_recommend')->label('5.13 Would recommend')->options($yesNo),
                Forms\Components\Textarea::make('improvement')->label('5.14 One thing to improve')->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('Declaration')->schema([
                Forms\Components\TextInput::make('declaration_name')->label('Name'),
                Forms\Components\DatePicker::make('declaration_date')->label('Date'),
            ])->columns(2),
        ])->disabled();
    }

    public static function table(Table $table): Table
    {
        $label = fn (array $options) => fn ($state) => $state === null ? '—' : ($options[$state] ?? $state);
        $bool = fn ($state) => $state === null ? '—' : ($state ? 'Yes' : 'No');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Learner')->searchable(),
                Tables\Columns\TextColumn::make('course.title')->label('Course')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('stage')->badge()->formatStateUsing($label(PP::SURVEY_STAGES)),
                Tables\Columns\TextColumn::make('work_change')->label('Work change')->formatStateUsing($label(PP::WORK_CHANGE))->wrap(),
                Tables\Columns\TextColumn::make('started_enterprise')->label('Enterprise')->formatStateUsing($bool),
                Tables\Columns\TextColumn::make('income_band')->label('Earnings')->formatStateUsing($label(PP::INCOME + PP::INCOME_LEGACY)),
                Tables\Columns\TextColumn::make('rating')->formatStateUsing($label(PP::RATING)),
                Tables\Columns\TextColumn::make('would_recommend')->label('Recommend')->formatStateUsing($bool),
                Tables\Columns\TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')->options(PP::SURVEY_STAGES),
                Tables\Filters\SelectFilter::make('course_id')->label('Course')->relationship('course', 'title')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('rating')->options(PP::RATING),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseOutcomeSurveys::route('/'),
            'view' => Pages\ViewCourseOutcomeSurvey::route('/{record}'),
        ];
    }
}
