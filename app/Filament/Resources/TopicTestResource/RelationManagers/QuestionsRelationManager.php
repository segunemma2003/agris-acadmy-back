<?php

namespace App\Filament\Resources\TopicTestResource\RelationManagers;

use App\Filament\Support\QuestionsRelationManager as BaseQuestionsRelationManager;
use App\Models\TopicTestQuestion;

class QuestionsRelationManager extends BaseQuestionsRelationManager
{
    protected function questionModel(): string
    {
        return TopicTestQuestion::class;
    }

    protected function foreignKey(): string
    {
        return 'topic_test_id';
    }
}
