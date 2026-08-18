<?php

namespace App\Enums;

enum CertificateSource: string
{
    case Generated = 'generated';
    case Uploaded = 'uploaded';

    public function labelFa(): string
    {
        return match ($this) {
            self::Generated => 'تولیدشده توسط سایت',
            self::Uploaded => 'فایل آپلودشده',
        };
    }
}
