<?php

namespace App\Filament\Tutor\Resources;

use App\Filament\Tutor\Resources\CourseDiySubmissionResource\Pages;
use App\Models\CourseDiySubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

/** Learners' DIY work: what they recorded (with photos), approve or send back with feedback. */
class CourseDiySubmissionResource extends Resource
{
    protected static ?string $model = CourseDiySubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'DIY Submissions';

    protected static ?string $modelLabel = 'DIY submission';

    protected static ?string $navigationGroup = 'Course Management';

    protected static ?int $navigationSort = 9;

    public const STATUSES = ['in_progress' => 'In progress', 'submitted' => 'Handed in — to review', 'approved' => 'Approved', 'needs_work' => 'Needs work'];

    public static function courseScope(Builder $query): Builder
    {
        return $query->accessibleByTutor(Auth::id());
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['diy', 'user', 'course'])->whereHas('course', fn ($q) => static::courseScope($q));
    }

    public static function getNavigationBadge(): ?string
    {
        $n = static::getEloquentQuery()->where('status', 'submitted')->count();

        return $n ? (string) $n : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('The learner\'s work')
                ->schema([
                    Forms\Components\Placeholder::make('work')
                        ->hiddenLabel()
                        ->content(fn (?CourseDiySubmission $record) => $record ? new HtmlString(static::workHtml($record)) : ''),
                ]),
            Forms\Components\Section::make('Your review')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options(self::STATUSES)
                        ->required(),
                    Forms\Components\Textarea::make('tutor_feedback')
                        ->label('Feedback to the learner')
                        ->rows(4)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    /** Steps ticked, and every entry as a row (photos as thumbnails). */
    public static function workHtml(CourseDiySubmission $s): string
    {
        $e = fn ($v) => e((string) $v);
        $diy = $s->diy;
        $fields = $diy?->fields() ?? [];
        $steps = $diy?->steps ?? [];
        $done = array_flip($s->steps_done ?? []);
        $html = '<div style="font-size:14px;line-height:1.5">';
        $html .= '<p><strong>'.$e($s->user?->name).'</strong> · '.$e($diy?->title).' · '.$e($s->course?->title).'</p>';
        if ($steps) {
            $html .= '<p style="margin-top:8px"><strong>Steps</strong> ('.count(array_intersect_key($done, $steps)).' of '.count($steps).' done)</p><ul style="margin:4px 0 12px 18px;list-style:disc">';
            foreach ($steps as $i => $st) {
                $html .= '<li>'.(isset($done[$i]) ? '✅ ' : '⬜ ').$e($st['title'] ?? '').'</li>';
            }
            $html .= '</ul>';
        }
        $entries = $s->entriesForDisplay();
        $html .= '<p><strong>Records</strong> ('.count($entries).' of '.max(1, (int) $diy?->entries_required).')</p>';
        if (! $entries) {
            return $html.'<p style="color:#888">Nothing recorded yet.</p></div>';
        }
        $html .= '<div style="overflow-x:auto"><table style="border-collapse:collapse;width:100%;margin-top:6px"><thead><tr><th style="text-align:left;padding:6px;border-bottom:1px solid #ddd">'.$e($diy?->entry_label ?: 'Entry').'</th>';
        foreach ($fields as $f) {
            $html .= '<th style="text-align:left;padding:6px;border-bottom:1px solid #ddd">'.$e($f['label']).($f['unit'] ? ' ('.$e($f['unit']).')' : '').'</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($entries as $row) {
            $html .= '<tr><td style="padding:6px;border-bottom:1px solid #eee;white-space:nowrap">'.$e($row['n']).'<br><span style="color:#888;font-size:12px">'.$e(substr((string) $row['recorded_at'], 0, 10)).'</span></td>';
            foreach ($fields as $f) {
                $v = $row['values'][$f['key']] ?? null;
                if ($f['type'] === 'photo' && $v) {
                    $cell = '<a href="'.$e($v).'" target="_blank"><img src="'.$e($v).'" style="height:72px;border-radius:6px"></a>';
                } elseif ($f['type'] === 'check') {
                    $cell = $v === null ? '—' : ($v ? 'Yes' : 'No');
                } else {
                    $cell = $v === null || $v === '' ? '—' : $e($v);
                }
                $html .= '<td style="padding:6px;border-bottom:1px solid #eee;vertical-align:top">'.$cell.'</td>';
            }
            $html .= '</tr>';
        }

        return $html.'</tbody></table></div></div>';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Learner')->searchable(),
                Tables\Columns\TextColumn::make('diy.title')->label('Practical')->wrap(),
                Tables\Columns\TextColumn::make('course.title')->label('Course')->toggleable(),
                Tables\Columns\TextColumn::make('entries')->label('Records')->state(fn ($record) => count($record->entries ?? []).' / '.max(1, (int) $record->diy?->entries_required)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => ['submitted' => 'warning', 'approved' => 'success', 'needs_work' => 'danger'][$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('submitted_at')->label('Handed in')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::STATUSES),
                Tables\Filters\SelectFilter::make('course_diy_content_id')
                    ->label('Practical')
                    ->relationship('diy', 'title', fn ($query) => $query->whereHas('course', fn ($c) => static::courseScope($c))),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Review'),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'submitted')
                    ->action(fn ($record) => $record->update(['status' => 'approved', 'reviewed_by' => Auth::id(), 'reviewed_at' => now()])),
            ])
            ->defaultSort(fn ($query) => $query->orderByRaw("CASE status WHEN 'submitted' THEN 0 WHEN 'needs_work' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")->orderByDesc('updated_at'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseDiySubmissions::route('/'),
            'edit' => Pages\EditCourseDiySubmission::route('/{record}/edit'),
        ];
    }
}
