<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case BLOCKED = 'blocked';
    case IN_REVIEW = 'in_review';
    case WAITING_FOR_TEST = 'waiting_for_test';
    case TESTED = 'tested';
    case DEPLOYED = 'deployed';

    public static function values(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }
}

