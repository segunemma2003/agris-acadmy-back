<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatbotLeadResource\Pages;
use App\Models\ChatbotIntakeAnswer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChatbotLeadResource extends Resource
{
    protected static ?string $model = ChatbotIntakeAnswer::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationGroup = 'Leads & Onboarding';

    protected static ?string $navigationLabel = 'Chatbot & USSD Leads';

    protected static ?string $pluralModelLabel = 'Chatbot & USSD Leads';

    protected static ?string $modelLabel = 'Lead';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lead Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')->disabled(),
                        Forms\Components\TextInput::make('phone')->disabled(),
                        Forms\Components\Select::make('source')
                            ->options(['chatbot' => 'Chatbot', 'ussd' => 'USSD'])
                            ->disabled(),
                        Forms\Components\TextInput::make('occupation')->disabled(),
                        Forms\Components\TextInput::make('state_lga')->label('State / LGA')->disabled(),
                        Forms\Components\TextInput::make('learning_goal')->disabled(),
                        Forms\Components\TextInput::make('experience_level')->disabled(),
                        Forms\Components\Select::make('preferred_language')
                            ->options(['en' => 'English', 'ha' => 'Hausa'])
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Course Interest')
                    ->schema([
                        Forms\Components\Select::make('interested_course_id')
                            ->relationship('interestedCourse', 'title')
                            ->disabled(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Linked User (after registration)')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('source')
                    ->colors(['primary' => 'chatbot', 'success' => 'ussd']),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('occupation')->toggleable(),
                Tables\Columns\TextColumn::make('state_lga')->label('State/LGA')->toggleable(),
                Tables\Columns\TextColumn::make('learning_goal')->toggleable(),
                Tables\Columns\TextColumn::make('interestedCourse.title')
                    ->label('Interested Course')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('user_id')
                    ->label('Registered')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options(['chatbot' => 'Chatbot', 'ussd' => 'USSD']),
                Tables\Filters\Filter::make('not_registered')
                    ->label('Not Yet Registered')
                    ->query(fn ($query) => $query->whereNull('user_id')),
                Tables\Filters\Filter::make('registered')
                    ->label('Registered')
                    ->query(fn ($query) => $query->whereNotNull('user_id')),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatbotLeads::route('/'),
            'view'  => Pages\ViewChatbotLead::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
