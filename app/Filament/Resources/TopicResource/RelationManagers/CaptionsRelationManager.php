<?php

namespace App\Filament\Resources\TopicResource\RelationManagers;

use App\Jobs\TranslateTopicCaptionsJob;
use App\Models\TopicCaption;
use App\Support\CaptionLanguages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Review and correct a lesson's captions. Each line is "[start → end] text";
 * edit the text (and times if needed). Edited captions aren't overwritten by
 * automatic translation.
 */
class CaptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'captions';

    protected static ?string $title = 'Captions';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $ready = $ownerRecord->captions()->where('status', 'ready')->count();
        return $ready ? $ready.'/'.count(CaptionLanguages::ALL) : null;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('cues_text')
                ->label('Captions')
                ->helperText('One caption per line: [start → end] text. Times are minutes:seconds.')
                ->rows(24)
                ->required()
                ->afterStateHydrated(fn ($component, ?TopicCaption $record) => $component->state(self::toText($record?->cues ?? []))),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (TopicCaption $r) => CaptionLanguages::ALL[$r->language] ?? $r->language)
            ->columns([
                Tables\Columns\TextColumn::make('language')->formatStateUsing(fn ($state) => CaptionLanguages::ALL[$state] ?? $state),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'ready' => 'success', 'pending' => 'warning', 'failed' => 'danger', default => 'gray',
                })->tooltip(fn (TopicCaption $r) => $r->error),
                Tables\Columns\TextColumn::make('lines')->getStateUsing(fn (TopicCaption $r) => count($r->cues ?? [])),
                Tables\Columns\TextColumn::make('source')->formatStateUsing(fn ($state) => match ($state) {
                    'youtube' => 'From YouTube', 'translated' => 'Auto-translated', 'edited' => 'Edited by hand', default => $state,
                }),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->visible(fn (TopicCaption $r) => $r->status === 'ready')
                    ->modalWidth('5xl')
                    ->using(function (TopicCaption $record, array $data) {
                        $cues = self::fromText((string) ($data['cues_text'] ?? ''));
                        if ($cues === []) {
                            Notification::make()->title('No caption lines found')->danger()->send();
                            return $record;
                        }
                        $record->update(['cues' => $cues, 'source' => 'edited', 'translated_count' => count($cues)]);
                        return $record;
                    }),
                Tables\Actions\Action::make('retranslate')
                    ->label(fn (TopicCaption $r) => $r->language === CaptionLanguages::SOURCE ? 'Re-translate all languages' : 'Re-translate')
                    ->icon('heroicon-o-language')
                    ->requiresConfirmation()
                    ->modalDescription('Translates again from the English captions (hand edits in that language are replaced).')
                    ->visible(fn (TopicCaption $r) => $r->language !== CaptionLanguages::SOURCE || $r->status === 'ready')
                    ->action(function (TopicCaption $record) {
                        $languages = $record->language === CaptionLanguages::SOURCE ? CaptionLanguages::targets() : [$record->language];
                        foreach ($languages as $language) {
                            TopicCaption::updateOrCreate(['topic_id' => $record->topic_id, 'language' => $language],
                                ['cues' => [], 'translated_count' => 0, 'status' => 'pending', 'source' => 'translated', 'error' => null]);
                            TranslateTopicCaptionsJob::dispatch($record->topic_id, $language);
                        }
                        Notification::make()->title('Translation queued')->success()->send();
                    }),
            ])
            ->defaultSort('id')
            ->emptyStateHeading('No captions yet')
            ->emptyStateDescription('Connect the YouTube channel under “YouTube captions”; captions for this lesson are then downloaded and translated automatically.');
    }

    public static function toText(array $cues): string
    {
        $t = fn (float $s) => sprintf('%02d:%05.2f', intdiv((int) $s, 60), fmod($s, 60));
        return collect($cues)->map(fn ($c) => '['.$t($c['start']).' → '.$t($c['end']).'] '.$c['text'])->implode("\n");
    }

    public static function fromText(string $text): array
    {
        $seconds = fn (string $v) => str_contains($v, ':')
            ? (float) explode(':', $v)[0] * 60 + (float) explode(':', $v)[1]
            : (float) $v;
        $cues = [];
        foreach (preg_split('/\r?\n/', $text) as $line) {
            if (preg_match('/^\s*\[\s*([\d:.]+)\s*(?:→|->|-)\s*([\d:.]+)\s*\]\s*(.+)$/u', $line, $m)) {
                $cues[] = ['start' => round($seconds($m[1]), 2), 'end' => round($seconds($m[2]), 2), 'text' => trim($m[3])];
            }
        }
        return $cues;
    }
}
