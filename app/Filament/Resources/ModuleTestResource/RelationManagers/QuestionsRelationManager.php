<?php

namespace App\Filament\Resources\ModuleTestResource\RelationManagers;

use App\Filament\Support\QuestionsRelationManager as BaseQuestionsRelationManager;
use App\Models\TestQuestion;

class QuestionsRelationManager extends BaseQuestionsRelationManager
{
    protected function questionModel(): string
    {
        return TestQuestion::class;
    }

    protected function foreignKey(): string
    {
        return 'module_test_id';
    }
}
