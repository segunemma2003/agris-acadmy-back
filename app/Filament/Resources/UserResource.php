<?php

namespace App\Filament\Resources;

use App\Filament\Exports\UserExporter;
use App\Filament\Resources\UserResource\Pages;
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
                            ->helperText('Programme Reports is always available even if nothing else is ticked. Select Platform Overview plus any other analytics sections this funder should see.')
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

