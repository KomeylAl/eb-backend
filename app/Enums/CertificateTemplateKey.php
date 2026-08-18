<?php

namespace App\Enums;

enum CertificateTemplateKey: string
{
    case Classic = 'classic';
    case Minimal = 'minimal';
    case Formal = 'formal';
    case Uploaded = 'uploaded';

    public function labelFa(): string
    {
        return match ($this) {
            self::Classic => 'کلاسیک',
            self::Minimal => 'مینیمال',
            self::Formal => 'رسمی',
            self::Uploaded => 'فایل آپلودشده',
        };
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['key' => $case->value, 'label' => $case->labelFa()],
            array_values(array_filter(
                self::cases(),
                fn (self $case) => $case !== self::Uploaded,
            )),
        );
    }
}
