<?php

namespace App\Filament\Pages;

use App\Support\SmsNudgeSettings;
use Filament\Actions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class NotificationSettings extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static string $view = 'filament.pages.notification-settings';

    protected static ?string $navigationLabel = 'Notification settings';

    protected static ?string $title = 'SMS nudge settings';

    protected static ?string $navigationGroup = 'System Management';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'settings/notifications';

    public ?array $data = [];

    public ?string $previewText = null;

    public function mount(): void
    {
        $this->form->fill(SmsNudgeSettings::all());
        $this->previewText = null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('SMS inactivity nudges')
                    ->description('Configure the learner SMS campaign. Changes apply on the next scheduled job run.')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('Enable SMS nudges')
                            ->helperText('When off, the scheduled job skips sending.')
                            ->inline(false),
                        Forms\Components\TextInput::make('inactivity_threshold_days')
                            ->label('Inactivity threshold (days)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(90)
                            ->required()
                            ->helperText('1st SMS after this many inactive days; 2nd SMS after 2× this (e.g. 7 then 14).'),
                        Forms\Components\Select::make('send_time')
                            ->label('Send time')
                            ->options(SmsNudgeSettings::SEND_TIMES)
                            ->required()
                            ->helperText('The daily job only sends inside this window (Africa/Lagos server time).'),
                        Forms\Components\TextInput::make('opt_out_keyword')
                            ->label('Opt-out keyword')
                            ->required()
                            ->maxLength(32)
                            ->helperText('Exact inbound SMS keyword that opts the learner out (e.g. STOP).'),
                        Forms\Components\Textarea::make('message_template')
                            ->label('Message template')
                            ->rows(5)
                            ->required()
                            ->helperText('Supported merge tags: '.implode(', ', SmsNudgeSettings::MERGE_TAGS))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Preview SMS')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->action('previewSms'),
            Actions\Action::make('save')
                ->label('Save settings')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function previewSms(): void
    {
        $state = $this->form->getState();
        $template = (string) ($state['message_template'] ?? '');
        $this->previewText = SmsNudgeSettings::render($template, SmsNudgeSettings::previewReplacements());

        $keyword = strtoupper(trim((string) ($state['opt_out_keyword'] ?? 'STOP')));
        if ($keyword !== '' && ! str_contains(strtoupper($this->previewText), $keyword)) {
            $this->previewText = rtrim($this->previewText).' Reply '.$keyword.' to opt out.';
        }

        Notification::make()
            ->title('Preview rendered')
            ->body('Review the sample SMS below before saving.')
            ->success()
            ->send();
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $saved = SmsNudgeSettings::save($state);
        $this->form->fill($saved);

        Notification::make()
            ->title('Notification settings saved')
            ->body('Changes take effect on the next scheduled SMS nudge run.')
            ->success()
            ->send();
    }
}
