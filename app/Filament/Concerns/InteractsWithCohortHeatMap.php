<?php

namespace App\Filament\Concerns;

use App\Services\CohortHeatMapService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

trait InteractsWithCohortHeatMap
{
    use InteractsWithForms;

    public ?array $data = [];

    public ?int $selectedLearnerId = null;

    /** @var array<int, array<string, mixed>> */
    public array $visibleLearners = [];

    public ?string $cacheGeneratedAt = null;

    public bool $cacheStale = false;

    /** @var array<int, string> */
    public array $courseOptions = [];

    /** @var array<int, array{label: string, course_id: int}> */
    public array $moduleOptions = [];

    abstract protected function heatMapFacilitatorScope(): ?int;

    abstract protected function canForceRefreshCache(): bool;

    public function mount(): void
    {
        $this->form->fill([
            'course_id' => null,
            'module_id' => null,
            'band' => null,
        ]);

        $this->reloadFromCache();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('course_id')
                    ->label('Course')
                    ->placeholder('All courses')
                    ->options(fn () => $this->courseOptions)
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->data['module_id'] = null;
                        $this->applyFilters();
                    }),
                Select::make('module_id')
                    ->label('Module')
                    ->placeholder('All modules')
                    ->options(function () {
                        $courseId = $this->data['course_id'] ?? null;
                        $options = [];
                        foreach ($this->moduleOptions as $id => $meta) {
                            if ($courseId && (int) $meta['course_id'] !== (int) $courseId) {
                                continue;
                            }
                            $options[$id] = $meta['label'];
                        }

                        return $options;
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->applyFilters()),
                Select::make('band')
                    ->label('Progress band')
                    ->placeholder('All bands')
                    ->options([
                        'red' => 'Red (0–25%)',
                        'amber' => 'Amber (26–50%)',
                        'teal' => 'Teal (51–75%)',
                        'green' => 'Green (76–100%)',
                    ])
                    ->live()
                    ->afterStateUpdated(fn () => $this->applyFilters()),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function applyFilters(): void
    {
        $service = app(CohortHeatMapService::class);
        $payload = $service->getCached($this->heatMapFacilitatorScope());
        $this->cacheGeneratedAt = $payload['generated_at'] ?? null;
        $this->cacheStale = (bool) ($payload['stale'] ?? false);

        $raw = $payload['learners'] ?? [];
        $options = $service->filterOptions($raw);
        $this->courseOptions = $options['courses'];
        $this->moduleOptions = $options['modules'];

        $this->visibleLearners = $service->filterLearners(
            $raw,
            isset($this->data['course_id']) && $this->data['course_id'] !== '' && $this->data['course_id'] !== null
                ? (int) $this->data['course_id']
                : null,
            isset($this->data['module_id']) && $this->data['module_id'] !== '' && $this->data['module_id'] !== null
                ? (int) $this->data['module_id']
                : null,
            $this->data['band'] ?? null,
        );
    }

    public function reloadFromCache(): void
    {
        $this->applyFilters();
    }

    public function refreshCacheNow(): void
    {
        if (!$this->canForceRefreshCache()) {
            Notification::make()->title('Not allowed')->danger()->send();

            return;
        }

        app(CohortHeatMapService::class)->refreshAll();
        $this->reloadFromCache();

        Notification::make()
            ->title('Heat map cache refreshed')
            ->success()
            ->send();
    }

    public function openLearner(int $learnerId): void
    {
        $this->selectedLearnerId = $learnerId;
        $this->mountAction('learnerDetail');
    }

    public function learnerDetailAction(): Action
    {
        return Action::make('learnerDetail')
            ->slideOver()
            ->modalHeading(fn () => $this->selectedLearner()['name'] ?? 'Learner detail')
            ->modalDescription('Individual progress detail (from daily heat map snapshot)')
            ->modalContent(fn () => view('filament.shared.cohort-heat-map-learner-detail', [
                'learner' => $this->selectedLearner(),
                'bands' => CohortHeatMapService::BANDS,
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function selectedLearner(): ?array
    {
        if (!$this->selectedLearnerId) {
            return null;
        }

        foreach ($this->visibleLearners as $learner) {
            if ((int) $learner['id'] === (int) $this->selectedLearnerId) {
                return $learner;
            }
        }

        $payload = app(CohortHeatMapService::class)->getCached($this->heatMapFacilitatorScope());
        foreach ($payload['learners'] ?? [] as $learner) {
            if ((int) $learner['id'] === (int) $this->selectedLearnerId) {
                $progress = app(CohortHeatMapService::class)->resolveProgress(
                    $learner,
                    isset($this->data['course_id']) ? (int) $this->data['course_id'] : null,
                    isset($this->data['module_id']) ? (int) $this->data['module_id'] : null,
                ) ?? (float) ($learner['overall_progress'] ?? 0);

                $band = CohortHeatMapService::bandFor($progress);
                $learner['display_progress'] = round($progress, 1);
                $learner['display_band'] = $band;
                $learner['display_band_color'] = CohortHeatMapService::bandColor($band);
                $learner['display_band_label'] = CohortHeatMapService::BANDS[$band]['label'];

                return $learner;
            }
        }

        return null;
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('reload')
                ->label('Reload snapshot')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->reloadFromCache()),
        ];

        if ($this->canForceRefreshCache()) {
            $actions[] = Action::make('forceRefresh')
                ->label('Rebuild cache now')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Rebuild heat map cache?')
                ->modalDescription('This recomputes progress for all learners. Prefer the daily scheduled job in production.')
                ->action(fn () => $this->refreshCacheNow());
        }

        return $actions;
    }

    public function bandCounts(): array
    {
        $counts = ['red' => 0, 'amber' => 0, 'teal' => 0, 'green' => 0];
        foreach ($this->visibleLearners as $learner) {
            $band = $learner['display_band'] ?? 'red';
            if (isset($counts[$band])) {
                $counts[$band]++;
            }
        }

        return $counts;
    }
}
