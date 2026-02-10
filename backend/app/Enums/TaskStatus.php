<?php

namespace App\Enums;

use Illuminate\Validation\Rule;

final class TaskStatus
{
    public const TODO = 'todo';
    public const IN_PROGRESS = 'in_progress';
    public const IN_REVIEW = 'in_review';
    public const DONE = 'done';

    public static function all(): array
    {
        return [self::TODO, self::IN_PROGRESS, self::IN_REVIEW, self::DONE];
    }

    public static function rules(): array
    {
        return ['required', 'string', Rule::in(self::all())];
    }
}
