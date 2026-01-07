<?php

namespace App\Filament\Tutor\Resources\TopicResource\Pages;

use App\Filament\Tutor\Resources\TopicResource;
use App\Models\Assignment;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTopic extends EditRecord
{
    protected static string $resource = TopicResource::class;

    protected function afterSave(): void
    {
        $topic = $this->record;
        $module = $topic->module;
        
        // Auto-populate course_id, module_id, topic_id, and tutor_id for assignments
        // This ensures all assignments linked to this topic have the correct relationships
        $topic->assignments()->update([
            'course_id' => $module->course_id,
            'module_id' => $module->id,
            'topic_id' => $topic->id,
            'tutor_id' => Auth::id(),
        ]);
    }
}

