<?php

namespace App\Filament\Tutor\Resources\TopicTestResource\RelationManagers;

use App\Filament\Resources\TopicTestResource\RelationManagers\QuestionsRelationManager as Base;

/** Topic test questions — shared with admin (fixes options being corrupted on edit). */
class QuestionsRelationManager extends Base
{
}
