<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganisationResource\Pages;
use App\Models\Organisation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrganisationResource extends Resource
{
    protected static ?string $model = Organisation::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Partners (Organisations)';

    protected static ?string $modelLabel = 'Partner';

    protected static ?string $pluralModelLabel = 'Partners';

    protected static ?string $navigationGroup = 'User Management';

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Partner Contact Account')
                    ->description('Creates the login the partner will use on the partner dashboard.')
                    ->schema([
                        Forms\Components\TextInput::make('contact_name')
                            ->label('Contact name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Contact email')
                            ->email()
                            ->required()
                            ->unique(table: 'users', column: 'email'),
                        Forms\Components\TextInput::make('contact_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8),
                    ])
                    ->columns(2)
                    ->visibleOn('create'),
                Forms\Components\Section::make('Organisation')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('sector'),
                        Forms\Components\TextInput::make('state'),
                        Forms\Components\TextInput::make('lga'),
                        Forms\Components\TextInput::make('website'),
                    ])->columns(2),
                Forms\Components\Section::make('Approval')
                    ->schema([
                        Forms\Components\Toggle::make('is_approved')
                            ->label('Approved to post apprenticeship slots')
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('approved_at', $state ? now() : null)),
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),
                Forms\Components\Section::make('Partner Dashboard Access')
                    ->description('Choose exactly what this partner can see on their dashboard. Nothing checked = no dashboard access yet.')
                    ->schema([
                        Forms\Components\CheckboxList::make('dashboard_permissions')
                            ->label('Visible sections')
                            ->options(collect(config('partner_dashboard.sections'))->map(fn ($s) => $s['label'])->all())
                            ->helperText(fn ($state) => collect($state ?? [])
                                ->map(fn ($key) => config("partner_dashboard.sections.$key.description"))
                                ->filter()
                                ->implode(' '))
                            ->live()
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Contact')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sector')
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Organisation $record) => !$record->is_approved)
                    ->requiresConfirmation()
                    ->action(function (Organisation $record) {
                        $record->update([
                            'is_approved' => true,
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title("{$record->name} approved")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganisations::route('/'),
            'create' => Pages\CreateOrganisation::route('/create'),
            'edit' => Pages\EditOrganisation::route('/{record}/edit'),
        ];
    }
}
