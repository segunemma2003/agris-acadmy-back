<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\CourseDiyContentResource\Pages;
use App\Models\CourseDiyContent;
use App\Support\DiyTemplates;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * DIY practicals: step-by-step tasks learners do at home or on the farm, with an
 * observation form they fill in (once, or e.g. every day for a week) and hand in.
 */
class CourseDiyContentResource extends Resource
{
    protected static ?string $model = CourseDiyContent::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'DIY Practicals';

    protected static ?string $modelLabel = 'DIY practical';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 8;

    /** Courses this user may add DIY tasks to (admins: all). */
    public static function courseScope(Builder $query): Builder
    {
        return $query->accessibleByTutor(Auth::id());
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('course', fn ($q) => static::courseScope($q));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Start from a ready practical')
                ->description('Pick one to fill in the steps and the observation form, then change anything you like.')
                ->schema([
                    Forms\Components\Select::make('template')
                        ->label('Ready-made practical')
                        ->options(DiyTemplates::options())
                        ->placeholder('Start from scratch')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(function ($state, Set $set) {
                            $t = DiyTemplates::all()[$state] ?? null;
                            if (! $t) {
                                return;
                            }
                            foreach (['title', 'description', 'materials_needed', 'difficulty_level', 'estimated_time_minutes', 'entries_required', 'entry_label'] as $k) {
                                $set($k, $t[$k]);
                            }
                            $set('steps', collect($t['steps'])->mapWithKeys(fn ($s) => [(string) Str::uuid() => $s])->all());
                            $set('observation_fields', collect($t['observation_fields'])->mapWithKeys(fn ($f) => [(string) Str::uuid() => array_merge(
                                ['key' => 'f'.Str::lower(Str::random(6)), 'unit' => null, 'options' => [], 'required' => false, 'help' => null],
                                $f,
                            )])->all());
                        }),
                ])
                ->visibleOn('create'),

            Forms\Components\Section::make('About the practical')
                ->schema([
                    Forms\Components\Select::make('course_id')
                        ->label('Course')
                        ->relationship('course', 'title', fn ($query) => static::courseScope($query))
                        ->default(fn () => request()->integer('course_id') ?: null)
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('title')->required()->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->label('What learners will do and why')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TagsInput::make('materials_needed')
                        ->label('Materials needed')
                        ->placeholder('Type an item and press Enter')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('video_url')->label('Demonstration video link (optional)')->url()->maxLength(255),
                    Forms\Components\FileUpload::make('image')->label('Cover photo (optional)')->image()->disk('public')->directory('diy')->visibility('public'),
                    Forms\Components\Select::make('difficulty_level')
                        ->options(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'])
                        ->default('beginner'),
                    Forms\Components\TextInput::make('estimated_time_minutes')->label('Time each session (minutes)')->numeric()->default(30),
                ])->columns(2),

            Forms\Components\Section::make('Steps')
                ->description('The instructions learners follow, in order. They tick each one off.')
                ->schema([
                    Forms\Components\Repeater::make('steps')
                        ->hiddenLabel()
                        ->schema([
                            Forms\Components\TextInput::make('title')->label('Step')->required()->maxLength(160),
                            Forms\Components\Textarea::make('body')->label('How to do it')->rows(2),
                            Forms\Components\FileUpload::make('image')->label('Photo (optional)')->image()->disk('public')->directory('diy/steps')->visibility('public'),
                        ])
                        ->itemLabel(fn (array $state) => $state['title'] ?? null)
                        ->reorderable()
                        ->collapsible()
                        ->addActionLabel('Add step')
                        ->defaultItems(1),
                ]),

            Forms\Components\Section::make('What learners record')
                ->description('The observation form. Learners fill it in, add photos, and hand it in.')
                ->schema([
                    Forms\Components\Repeater::make('observation_fields')
                        ->hiddenLabel()
                        ->schema([
                            Forms\Components\Hidden::make('key')->default(fn () => 'f'.Str::lower(Str::random(6))),
                            Forms\Components\TextInput::make('label')->label('Question')->required()->maxLength(160)->columnSpan(2),
                            Forms\Components\Select::make('type')
                                ->label('Answer type')
                                ->options(['number' => 'Number (with a unit)', 'text' => 'Short text', 'choice' => 'Multiple choice', 'check' => 'Yes / no', 'photo' => 'Photo'])
                                ->default('text')
                                ->required()
                                ->live(),
                            Forms\Components\TextInput::make('unit')->label('Unit (e.g. °C, kg, cm)')->maxLength(20)->visible(fn (Get $get) => $get('type') === 'number'),
                            Forms\Components\TagsInput::make('options')->label('Choices')->visible(fn (Get $get) => $get('type') === 'choice')->columnSpan(2),
                            Forms\Components\Toggle::make('required')->label('Must answer')->inline(false),
                            Forms\Components\TextInput::make('help')->label('Hint for learners (optional)')->maxLength(200)->columnSpan(2),
                        ])
                        ->columns(4)
                        ->itemLabel(fn (array $state) => $state['label'] ?? null)
                        ->reorderable()
                        ->collapsible()
                        ->addActionLabel('Add question')
                        ->defaultItems(1),
                    Forms\Components\TextInput::make('entries_required')
                        ->label('How many times learners record')
                        ->helperText('1 = once. 7 with "Day" = a daily record for a week.')
                        ->numeric()->minValue(1)->maxValue(60)->default(1)->required(),
                    Forms\Components\TextInput::make('entry_label')
                        ->label('Each record is called')
                        ->helperText('e.g. Day, Week, Visit, Check')
                        ->default('Day')->maxLength(40),
                    Forms\Components\Toggle::make('requires_review')
                        ->label('I review the work (otherwise it completes when handed in)')
                        ->default(true),
                    Forms\Components\TextInput::make('sort_order')->label('Order in the course')->numeric()->default(0),
                    Forms\Components\Toggle::make('is_active')->label('Visible to learners')->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount([
                'submissions',
                'submissions as to_review_count' => fn ($q) => $q->where('status', 'submitted'),
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('course.title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('steps')->label('Steps')->state(fn ($record) => count($record->steps ?? [])),
                Tables\Columns\TextColumn::make('entries_required')->label('Records')->formatStateUsing(fn ($state, $record) => $state.' × '.strtolower($record->entry_label ?: 'entry')),
                Tables\Columns\TextColumn::make('submissions_count')->label('Learners')->sortable(),
                Tables\Columns\TextColumn::make('to_review_count')->label('To review')->badge()->color(fn ($state) => $state ? 'warning' : 'gray')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Visible')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'title', fn ($query) => static::courseScope($query))
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Review work')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn ($record) => static::submissionsUrl($record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function submissionsUrl($record): string
    {
        return CourseDiySubmissionResource::getUrl('index', ['tableFilters' => ['course_diy_content_id' => ['value' => $record->id]]]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseDiyContents::route('/'),
            'create' => Pages\CreateCourseDiyContent::route('/create'),
            'edit' => Pages\EditCourseDiyContent::route('/{record}/edit'),
        ];
    }
}
