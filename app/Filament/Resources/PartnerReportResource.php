<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerReportResource\Pages;
use App\Models\PartnerReport;
use App\Models\User;
use App\Services\NotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PartnerReportResource extends Resource
{
    protected static ?string $model = PartnerReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Partner Reports';

    protected static ?string $modelLabel = 'Partner Report';

    protected static ?string $pluralModelLabel = 'Partner Reports';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 3;

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
                Forms\Components\Section::make('Report details')
                    ->description('Send a titled programme report to a partner dashboard. Choose weekly, monthly, or another cadence.')
                    ->schema([
                        Forms\Components\Select::make('partner_id')
                            ->label('Partner (funder)')
                            ->options(
                                fn () => User::query()
                                    ->where('role', 'partner')
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required()
                            ->helperText('Only Partner (funder) accounts appear here.'),
                        Forms\Components\TextInput::make('title')
                            ->label('Report title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. June 2026 Monthly Impact Report'),
                        Forms\Components\Select::make('period_type')
                            ->label('Report period')
                            ->options([
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'annual' => 'Annual',
                                'custom' => 'Custom',
                            ])
                            ->default('monthly')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Period start'),
                        Forms\Components\DatePicker::make('period_end')
                            ->label('Period end'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false)
                            ->helperText('Published reports appear immediately on the partner dashboard.'),
                        Forms\Components\RichEditor::make('summary')
                            ->label('Summary / narrative')
                            ->columnSpanFull()
                            ->helperText('Optional narrative partners will see at the top of the report.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Programme outcomes')
                    ->description('Headline numbers for the period. Counts auto-sync from participant lists when you add rows.')
                    ->schema([
                        Forms\Components\TextInput::make('participants_registered_count')
                            ->label('Participants registered / reached')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('participants_selected_count')
                            ->label('Participants selected for the programme')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('participants_enrolled_count')
                            ->label('Enrolled (captured)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('jobs_enabled')
                            ->label('Jobs enabled')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('jobs_created')
                            ->label('Jobs created')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('demo_hubs')
                            ->label('Demo hubs / Dignity in labour')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('enterprises_created')
                            ->label('Enterprises created')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Participants registered / reached')
                    ->description('Detailed list partners can download from their dashboard.')
                    ->schema([
                        self::participantRepeater('participants_registered', 'participants_registered_count'),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('Participants selected for the programme')
                    ->schema([
                        self::participantRepeater('participants_selected', 'participants_selected_count'),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('Enrolled participants')
                    ->description('People captured as enrolled for this period.')
                    ->schema([
                        self::participantRepeater('participants_enrolled', 'participants_enrolled_count'),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('Activity links & media')
                    ->description('Share Google Docs and photo URLs from programme activities.')
                    ->schema([
                        Forms\Components\Repeater::make('google_doc_links')
                            ->label('Google Doc links')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Document title')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('url')
                                    ->label('Google Doc URL')
                                    ->url()
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->addActionLabel('Add Google Doc')
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('image_links')
                            ->label('Picture / activity image links')
                            ->schema([
                                Forms\Components\TextInput::make('caption')
                                    ->label('Caption')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('url')
                                    ->label('Image URL')
                                    ->url()
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['caption'] ?? 'Activity photo')
                            ->addActionLabel('Add image link')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function participantRepeater(string $field, string $countField): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make($field)
            ->label('Participant details')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(50),
                Forms\Components\Select::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                        'other' => 'Other',
                        'prefer_not_to_say' => 'Prefer not to say',
                    ])
                    ->native(false),
                Forms\Components\TextInput::make('state')
                    ->maxLength(100),
                Forms\Components\TextInput::make('lga')
                    ->label('LGA')
                    ->maxLength(100),
                Forms\Components\TextInput::make('occupation')
                    ->maxLength(150),
                Forms\Components\Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(3)
            ->defaultItems(0)
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
            ->addActionLabel('Add participant')
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set) use ($field, $countField) {
                $rows = $get($field) ?? [];
                $named = collect($rows)->filter(fn ($row) => filled($row['name'] ?? null))->count();
                if ($named > 0) {
                    $set($countField, $named);
                }
            })
            ->columnSpanFull();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('partner.name')
                    ->label('Partner')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_type')
                    ->label('Period')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'weekly' => 'info',
                        'monthly' => 'success',
                        'quarterly' => 'warning',
                        'annual' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('period_start')
                    ->label('From')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('period_end')
                    ->label('To')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('participants_enrolled_count')
                    ->label('Enrolled')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jobs_created')
                    ->label('Jobs')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ]),
                Tables\Filters\SelectFilter::make('period_type')
                    ->label('Period')
                    ->options([
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'annual' => 'Annual',
                        'custom' => 'Custom',
                    ]),
                Tables\Filters\SelectFilter::make('partner_id')
                    ->label('Partner')
                    ->relationship('partner', 'name', fn ($query) => $query->where('role', 'partner')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (PartnerReport $record) => $record->status !== 'published')
                    ->requiresConfirmation()
                    ->action(function (PartnerReport $record) {
                        $wasDraft = $record->status !== 'published';

                        $record->update([
                            'status' => 'published',
                            'published_at' => $record->published_at ?? now(),
                        ]);

                        if ($wasDraft && $record->partner) {
                            NotificationService::create(
                                $record->partner,
                                'partner_report_ready',
                                'New programme report',
                                "\"{$record->title}\" is now available on your partner dashboard.",
                                'partner_report',
                                $record->id,
                            );
                        }

                        Notification::make()
                            ->title('Report published')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListPartnerReports::route('/'),
            'create' => Pages\CreatePartnerReport::route('/create'),
            'view' => Pages\ViewPartnerReport::route('/{record}'),
            'edit' => Pages\EditPartnerReport::route('/{record}/edit'),
        ];
    }
}
