<?php

namespace App\Enums;

enum UserRole: string
{
    case CTO = 'cto';
    case LEAD_DEV = 'lead_dev';
    case DEVELOPER = 'developer';
    case PO = 'po';

    public static function values(): array
    {
        return array_map(static fn (self $role) => $role->value, self::cases());
    }
}

