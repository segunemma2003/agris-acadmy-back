<?php

namespace App\Filament\Support;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Lets the course-content relation managers serve both the admin and the
 * tutor panel: links open the current panel's own pages, and new content is
 * owned by the course's tutor (admin panel) or by the tutor adding it
 * (tutor panel, whose test pages only let a tutor edit their own tests).
 */
trait PanelAwareContent
{
    protected function inTutorPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'tutor';
    }

    protected function contentTutorId(?int $courseTutorId): ?int
    {
        return $this->inTutorPanel() ? Auth::id() : $courseTutorId;
    }

    /** Edit URL of `$record` on the current panel's resource named `$resource` (e.g. "ModuleResource"). */
    protected function contentUrl(string $resource, Model $record): string
    {
        $class = $this->inTutorPanel()
            ? "App\\Filament\\Tutor\\Resources\\{$resource}"
            : "App\\Filament\\Resources\\{$resource}";

        return $class::getUrl('edit', ['record' => $record]);
    }
}
