<?php

namespace App\Enums;

enum WorkshopType: string
{
    case General = 'general';
    case Specialized = 'specialized';
    case Webinar = 'webinar';
    case Seminar = 'seminar';

    public static function normalize(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Legacy menu query used `special`
        if ($value === 'special') {
            return self::Specialized->value;
        }

        return $value;
    }

    public function labelFa(): string
    {
        return match ($this) {
            self::General => 'کارگاه عمومی',
            self::Specialized => 'کارگاه تخصصی',
            self::Webinar => 'وبینار',
            self::Seminar => 'سمینار',
        };
    }
}
