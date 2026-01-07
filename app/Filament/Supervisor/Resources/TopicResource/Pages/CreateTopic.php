<?php

namespace App\Filament\Supervisor\Resources\TopicResource\Pages;

use App\Filament\Supervisor\Resources\TopicResource;
use App\Models\Assignment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTopic extends CreateRecord
{
    protected static string $resource = TopicResource::class;

    protected function afterCreate(): void
    {
        $topic = $this->record;
        $module = $topic->module;
        
        // Auto-populate course_id, module_id, topic_id, and tutor_id for assignments
        $topic->assignments()->update([
            'course_id' => $module->course_id,
            'module_id' => $module->id,
            'topic_id' => $topic->id,
            'tutor_id' => Auth::id(),
        ]);
    }
}

