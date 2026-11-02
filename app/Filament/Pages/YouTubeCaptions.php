<?php

namespace App\Filament\Pages;

use App\Filament\Resources\TopicResource;
use App\Jobs\SyncTopicCaptionsJob;
use App\Models\Topic;
use App\Models\TopicCaption;
use App\Models\YoutubeConnection;
use App\Services\YouTubeCaptionService;
use App\Support\CaptionLanguages;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/** Connect Agrisiti's YouTube channel and follow lesson captions/translations. */
class YouTubeCaptions extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-language';
    protected static string $view = 'filament.pages.youtube-captions';
    protected static ?string $navigationLabel = 'YouTube captions';
    protected static ?string $title = 'Lesson captions';
    protected static ?string $navigationGroup = 'Course Management';
    protected static ?string $slug = 'youtube-captions';

    public function getConnection(): ?YoutubeConnection
    {
        return YoutubeConnection::current();
    }

    public function getConfigured(): bool
    {
        return app(YouTubeCaptionService::class)->configured();
    }

    /** @return array{videos: int, english: int, complete: int, pending: int, missing: int} */
    public function getStats(): array
    {
        $videoTopicIds = Topic::where('is_active', true)->where('video_url', 'like', '%youtu%')->pluck('id');
        $ready = TopicCaption::whereIn('topic_id', $videoTopicIds)->where('status', 'ready')
            ->selectRaw('topic_id, count(*) as n')->groupBy('topic_id')->pluck('n', 'topic_id');

        return [
            'videos' => $videoTopicIds->count(),
            'english' => TopicCaption::whereIn('topic_id', $videoTopicIds)->where('language', 'en')->where('status', 'ready')->count(),
            'complete' => $ready->filter(fn ($n) => $n >= count(CaptionLanguages::ALL))->count(),
            'pending' => TopicCaption::whereIn('topic_id', $videoTopicIds)->where('status', 'pending')->count(),
            'missing' => TopicCaption::whereIn('topic_id', $videoTopicIds)->where('language', 'en')->whereIn('status', ['unavailable', 'failed'])->count(),
        ];
    }

    protected function getHeaderActions(): array
    {
        $connected = (bool) $this->getConnection();

        return [
            Actions\Action::make('connect')
                ->label($connected ? 'Reconnect YouTube' : 'Connect YouTube channel')
                ->icon('heroicon-o-link')
                ->color($connected ? 'gray' : 'primary')
                ->url(route('admin.youtube.connect')),
            Actions\Action::make('syncAll')
                ->label('Fetch new captions now')
                ->icon('heroicon-o-arrow-path')
                ->visible($connected)
                ->action(function () {
                    \Artisan::call('captions:sync');
                    Notification::make()->title(trim(\Artisan::output()) ?: 'Queued')->success()->send();
                }),
            Actions\Action::make('disconnect')
                ->label('Disconnect')
                ->color('danger')
                ->icon('heroicon-o-x-mark')
                ->visible($connected)
                ->requiresConfirmation()
                ->modalDescription('Captions already downloaded stay. New videos will not get captions until you connect again.')
                ->action(function () {
                    app(YouTubeCaptionService::class)->disconnect();
                    Notification::make()->title('YouTube disconnected')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $languages = count(CaptionLanguages::ALL);

        return $table
            ->query(Topic::query()->where('video_url', 'like', '%youtu%')->with(['module.course', 'captions']))
            ->columns([
                Tables\Columns\TextColumn::make('module.course.title')->label('Course')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('title')->label('Lesson')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('english_status')
                    ->label('English')
                    ->badge()
                    ->getStateUsing(fn (Topic $r) => $r->captions->firstWhere('language', 'en')?->status ?? 'not fetched')
                    ->color(fn (string $state) => match ($state) {
                        'ready' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'unavailable' => 'gray', default => 'gray',
                    })
                    ->tooltip(fn (Topic $r) => $r->captions->firstWhere('language', 'en')?->error),
                Tables\Columns\TextColumn::make('languages_ready')
                    ->label('Languages ready')
                    ->getStateUsing(fn (Topic $r) => $r->captions->where('status', 'ready')->count()."/{$languages}")
                    ->description(fn (Topic $r) => $r->captions->where('status', 'pending')->pluck('language')->map(fn ($c) => CaptionLanguages::ALL[$c] ?? $c)->implode(', ') ?: null),
            ])
            ->actions([
                Tables\Actions\Action::make('regenerate')
                    ->label('Re-fetch')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn () => (bool) $this->getConnection())
                    ->requiresConfirmation()
                    ->modalDescription('Downloads the captions from YouTube again and re-translates every language (hand edits are replaced).')
                    ->action(function (Topic $record) {
                        TopicCaption::where('topic_id', $record->id)->update(['source' => 'translated']);
                        SyncTopicCaptionsJob::dispatch($record->id);
                        Notification::make()->title('Queued')->success()->send();
                    }),
                Tables\Actions\Action::make('edit')
                    ->label('Review / edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Topic $record) => TopicResource::getUrl('edit', ['record' => $record]).'?activeRelationManager=captions'),
            ])
            ->defaultSort('id', 'desc');
    }
}
