<?php

namespace App\Services;

use App\Models\User;

/**
 * Swaps a model's English fields for their Hausa translation when the
 * requesting user's locale is Hausa. Falls back to English (and flags
 * `needs_translation`) when a Hausa translation hasn't been generated yet.
 */
class ContentLocalizer
{
    /**
     * @param array<string> $fields Base field names, e.g. ['title', 'description']
     */
    public static function apply($model, ?User $user, array $fields): void
    {
        if (!$model || ($user?->locale ?? 'en') !== 'ha') {
            return;
        }

        $needsTranslation = false;

        foreach ($fields as $field) {
            $haField = "{$field}_ha";
            $haValue = $model->{$haField} ?? null;

            if (!empty($haValue)) {
                $model->{$field} = $haValue;
            } else {
                $needsTranslation = true;
            }
        }

        $model->needs_translation = $needsTranslation;
    }

    /**
     * Apply localization to every model in a collection.
     */
    public static function applyToCollection($collection, ?User $user, array $fields): void
    {
        foreach ($collection as $model) {
            static::apply($model, $user, $fields);
        }
    }
}
