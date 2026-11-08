<?php

namespace App\Filament\Resources;

use App\Filament\Exports\UserExporter;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('location')
                            ->label('Location')
                            ->helperText('Optional: State or location (e.g., Rivers, Lagos)')
                            ->maxLength(255),
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'tutor' => 'Tutor',
                                'student' => 'Student',
                                'tagdev' => 'TagDev',
                                'facilitator' => 'Facilitator',
                                'organisation' => 'Organisation (host)',
                                'partner' => 'Partner (funder)',
                            ])
                            ->required()
                            ->default('student')
                            ->live()
                            ->helperText('Organisation = hosts interns. Partner = funder who views the program dashboard. Only admins create either.'),
                        Forms\Components\Textarea::make('bio')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('avatar')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('avatars')
                            ->maxSize(200) // 200KB
                            ->preserveFilenames()
                            ->imagePreviewHeight('150')
                            ->panelAspectRatio('1:1')
                            ->avatar()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('400')
                            ->imageResizeTargetHeight('400')
                            ->helperText('Recommended: 400×400px (1:1 square). Max 200KB.')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(2),
                Forms\Components\Section::make('Partner (funder) access')
                    ->description('Partners gave funding and can view program analytics. They do not host interns. Programme Reports always appears for partners (reports you send them). Tick other sections they should see.')
                    ->schema([
                        Forms\Components\CheckboxList::make('dashboard_permissions')
                            ->label('Partner dashboard sections')
                            ->options(
                                collect(config('partner_dashboard.sections'))
                                    ->reject(fn ($_, $key) => $key === 'reports')
                                    ->map(fn ($s) => $s['label'])
                                    ->all()
                            )
                            ->default(fn () => array_values(array_filter(
                                array_keys(config('partner_dashboard.sections')),
                                fn ($key) => $key !== 'reports'
                            )))
                            ->helperText('Programme Reports is always available. Course Performance and Certificates are no longer offered on the partner dashboard.')
                            ->columns(2)
                            ->columnSpanFull()
                            ->dehydrated(fn (callable $get) => $get('role') === 'partner'),
                    ])
                    ->visible(fn (callable $get) => $get('role') === 'partner'),
                Forms\Components\Section::make('Organisation (host) profile')
                    ->description('Organisations host interns and post internship slots. They do not get the partner analytics dashboard.')
                    ->schema([
                        Forms\Components\TextInput::make('organisation_name')
                            ->label('Organisation name')
                            ->required(fn (callable $get) => $get('role') === 'organisation')
                            ->maxLength(255)
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('organisation_sector')
                            ->label('Sector')
                            ->maxLength(255)
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('organisation_state')
                            ->label('State')
                            ->maxLength(255)
                            ->dehydrated(false),
                        Forms\Components\Toggle::make('organisation_is_approved')
                            ->label('Approved to post internship slots')
                            ->default(false)
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->visible(fn (callable $get) => $get('role') === 'organisation'),
                Forms\Components\Section::make('Facilitator coverage (auto-assign by location)')
                    ->description('Learners are assigned automatically on register / state-LGA change: LGA match first, then state. Leave coverage empty and students in those areas will queue for manual assignment.')
                    ->schema([
                        Forms\Components\TagsInput::make('covered_states')
                            ->label('Covered states')
                            ->placeholder('Add a state name (e.g. Kano)')
                            ->helperText('Must match the learner\'s state field exactly for auto-assignment.'),
                        Forms\Components\TagsInput::make('covered_lgas')
                            ->label('Covered LGAs')
                            ->placeholder('Add an LGA name')
                            ->helperText('Preferred match when both state and LGA are set on the learner.'),
                    ])
                    ->columns(2)
                    ->visible(fn (callable $get) => $get('role') === 'facilitator'),
                Forms\Components\Section::make('Learner facilitator assignment')
                    ->description('Override automatic location-based assignment when needed. Queued learners had no matching facilitator coverage.')
                    ->schema([
                        Forms\Components\Select::make('facilitator_id')
                            ->label('Assigned facilitator')
                            ->relationship(
                                'facilitator',
                                'name',
                                fn ($query) => $query->where('role', 'facilitator')->where('is_active', true)
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Toggle::make('is_in_facilitator_queue')
                            ->label('In facilitator assignment queue')
                            ->helperText('Turn off after manually assigning a facilitator.'),
                    ])
                    ->columns(2)
                    ->visible(fn (callable $get) => $get('role') === 'student'),
                Forms\Components\Section::make('Participant profile')
                    ->description('Answers from the participant registration form (Parts 1–3). Consent is to the "Your Information, Your Choice" notice.')
                    ->schema([
                        Forms\Components\Placeholder::make('consent_status')
                            ->label('Data consent')
                            ->content(fn ($record) => $record?->consent_given_at
                                ? 'Yes — given ' . $record->consent_given_at->format('j M Y, H:i') . ' (notice version: ' . ($record->consent_version ?? '—') . ')'
                                : 'Not recorded (registered before the consent step, or via another channel)')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('first_name')->label('1.1 First name'),
                        Forms\Components\TextInput::make('surname')->label('1.2 Surname'),
                        Forms\Components\Select::make('nationality')->label('1.7 Nationality')->options(\App\Support\ParticipantProfile::NATIONALITY),
                        Forms\Components\Select::make('has_disability')->label('1.8 Disability or long-term difficulty')->options(\App\Support\ParticipantProfile::YES_NO_PREFER)->reactive(),
                        Forms\Components\CheckboxList::make('disability_types')->label('1.9 Type of difficulty, disability or neurodivergence')->options(\App\Support\ParticipantProfile::DISABILITY_TYPES + \App\Support\ParticipantProfile::DISABILITY_TYPES_LEGACY)->columns(2)->visible(fn (callable $get) => $get('has_disability') === 'yes'),
                        Forms\Components\Textarea::make('disability_description')->label('1.9a Self-described')->rows(2)->visible(fn (callable $get) => $get('has_disability') === 'yes'),
                        Forms\Components\Select::make('needs_accommodation')->label('1.9c Needs adjustments to participate fully')->options(\App\Support\ParticipantProfile::YES_NO)->reactive(),
                        Forms\Components\Textarea::make('accommodation_notes')->label('1.9c Adjustments needed')->rows(2)->visible(fn (callable $get) => $get('needs_accommodation') === 'yes'),
                        Forms\Components\Select::make('displacement_status')->label('1.10 Displacement')->options(\App\Support\ParticipantProfile::DISPLACEMENT),
                        Forms\Components\Select::make('women_category')->label('1.11 Which best describes you (women)')->options(\App\Support\ParticipantProfile::WOMEN_CATEGORY),
                        Forms\Components\Select::make('education_level')->label('1.12 Highest education')->options(\App\Support\ParticipantProfile::EDUCATION),
                        Forms\Components\Select::make('locality_type')->label('2.5 Rural, peri-urban or urban')->options(\App\Support\ParticipantProfile::LOCALITY),
                        Forms\Components\Select::make('learner_category')->label('3.1 Which best describes you')->options(\App\Support\ParticipantProfile::LEARNER_CATEGORY),
                        Forms\Components\Select::make('main_work')->label('3.2 Current employment status')->options(\App\Support\ParticipantProfile::MAIN_WORK + \App\Support\ParticipantProfile::MAIN_WORK_LEGACY),
                        Forms\Components\TextInput::make('job_title')->label('3.3 Job title / what they do'),
                        Forms\Components\Select::make('employment_type')->label('3.4 Full time, part time or seasonal')->options(\App\Support\ParticipantProfile::EMPLOYMENT_TYPE),
                        Forms\Components\TextInput::make('work_started_month')->label('3.5 Month started (1–12)')->numeric()->minValue(1)->maxValue(12),
                        Forms\Components\TextInput::make('work_started_year')->label('3.5 Year started')->numeric(),
                        Forms\Components\TextInput::make('work_organisation')->label('Organisation (where they work)')->datalist(\App\Support\ParticipantProfile::ORGANISATIONS),
                        Forms\Components\Select::make('work_sector')->label('3.6 Sector')->options(\App\Support\ParticipantProfile::SECTOR),
                        Forms\Components\Select::make('main_commodity')->label('3.7 Main commodity')->options(\App\Support\ParticipantProfile::COMMODITY),
                        Forms\Components\Select::make('income_band')->label('Monthly personal income (past 3 months avg)')->options(\App\Support\ParticipantProfile::INCOME + \App\Support\ParticipantProfile::INCOME_LEGACY),
                        Forms\Components\Select::make('value_chain')->label('3.8 Part of the value chain')->options(\App\Support\ParticipantProfile::VALUE_CHAIN),
                        Forms\Components\Select::make('value_chain_track')->label('WEO 3. Value chain track')->options(\App\Support\ParticipantProfile::VALUE_CHAIN_TRACK)->reactive(),
                        Forms\Components\TextInput::make('value_chain_track_other')->label('WEO 3. Other track')->visible(fn (callable $get) => $get('value_chain_track') === 'other'),
                        Forms\Components\Select::make('training_objective')->label('WEO 4. Post-training objective')->options(\App\Support\ParticipantProfile::TRAINING_OBJECTIVE),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn (callable $get) => $get('role') === 'student'),
                Forms\Components\Section::make('Password')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'organisation' => 'Organisation',
                        'partner' => 'Partner',
                        'tagdev' => 'TagDev',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'tutor' => 'warning',
                        'student' => 'success',
                        'tagdev' => 'info',
                        'facilitator' => 'primary',
                        'organisation' => 'warning',
                        'partner' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('facilitator.name')
                    ->label('Facilitator')
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_in_facilitator_queue')
                    ->label('Queued')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'tutor' => 'Tutor',
                        'student' => 'Student',
                        'tagdev' => 'TagDev',
                        'facilitator' => 'Facilitator',
                        'organisation' => 'Organisation (host)',
                        'partner' => 'Partner (funder)',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('is_in_facilitator_queue')
                    ->label('In facilitator queue'),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->label('Export for MEL')
                    ->exporter(UserExporter::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export for MEL')
                        ->exporter(UserExporter::class),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EnrollmentsRelationManager::class,
            RelationManagers\OutcomeSurveysRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

