<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificateTemplateResource\Pages;
use App\Models\CertificateTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;

class CertificateTemplateResource extends Resource
{
    protected static ?string $model = CertificateTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Course Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Template')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Certificate PDF')
                            ->disk('public')
                            ->directory('certificate-templates')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required()
                            ->helperText('Upload a single-page PDF certificate design. The participant name will be printed on top of it.'),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Use as default template')
                            ->helperText('The default template is pre-selected when generating certificates.'),
                    ])->columns(2),

                Forms\Components\Section::make('Name placement')
                    ->schema([
                        Forms\Components\TextInput::make('name_y_percent')
                            ->label('Vertical position (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.5)
                            ->default(50)
                            ->suffix('%')
                            ->helperText('Distance from the top of the page. The name is always centered horizontally.'),
                        Forms\Components\TextInput::make('font_size')
                            ->numeric()
                            ->minValue(6)
                            ->maxValue(120)
                            ->default(28),
                        Forms\Components\ColorPicker::make('font_color')
                            ->default('#141414'),
                        Forms\Components\Select::make('font_family')
                            ->options([
                                'Helvetica' => 'Helvetica',
                                'Times' => 'Times',
                                'Courier' => 'Courier',
                            ])
                            ->default('Helvetica')
                            ->required(),
                        Forms\Components\Select::make('font_style')
                            ->options([
                                '' => 'Regular',
                                'B' => 'Bold',
                                'I' => 'Italic',
                                'BI' => 'Bold Italic',
                            ])
                            ->default('B')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean(),
                Tables\Columns\TextColumn::make('name_y_percent')
                    ->label('Name Y%')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('font_size'),
                Tables\Columns\TextColumn::make('certificates_count')
                    ->counts('certificates')
                    ->label('Issued'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (CertificateTemplate $record): string => URL::temporarySignedRoute(
                        'admin.certificate-templates.preview',
                        now()->addMinutes(10),
                        ['certificateTemplate' => $record->id],
                    ))
                    ->openUrlInNewTab()
                    ->tooltip('Opens the actual certificate design with a sample name in a new tab'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateTemplates::route('/'),
            'create' => Pages\CreateCertificateTemplate::route('/create'),
            'edit' => Pages\EditCertificateTemplate::route('/{record}/edit'),
        ];
    }
}
