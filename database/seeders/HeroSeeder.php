<?php

namespace Database\Seeders;

use App\Models\HeroSetting;
use App\Models\HeroSlide;
use Illuminate\Database\Seeder;

class HeroSeeder extends Seeder
{
    public function run(): void
    {
        if (! HeroSetting::query()->exists()) {
            HeroSetting::query()->create([
                'autoplay_ms' => 5000,
            ]);
        }

        if (HeroSlide::query()->exists()) {
            return;
        }

        $slides = [
            [
                'title' => 'ارزیابی اولیه رایگان',
                'body' => 'به منظور تشخیص دقیق نیازهای مشاوره ای و درمانی مراجع و تعیین بهترین متخصصان حرفه ای لازم برای شما، یک مصاحبه اولیه مختصر (به صورت تلفنی و رایگان و در صورت ضرورت به صورت حضوری یا آنلاین) انجام خواهد شد.',
                'button_text' => 'دریافت نوبت ارزیابی اولیه رایگان',
                'button_link' => '/appointment/#assessment',
                'sort_order' => 0,
            ],
            [
                'title' => 'روانشناس آنکال',
                'body' => 'در صورت نیاز به گفتگوی اورژانسی با روانشناس در مواقع بسیار بحرانی، در طی ساعات کاری کلینیک (9 تا 21)، با شماره 09228728245 و در خارج از این بازه، با شماره 09939924313 تماس بگیرید تا بتوانید با روانشناس آنکال مرکز صحبت بفرمایید.',
                'button_text' => 'تماس با روانشناس آنکال',
                'button_link' => 'tel:09228728245',
                'sort_order' => 1,
            ],
            [
                'title' => 'مرکز جامع تخصصی مشاوره و رواندرمانی ابراز',
                'body' => 'با تاسیس و مدیریت دکتر علی محرابی، متخصص روانشناسی بالینی و عضو هیئت علمی دانشگاه اصفهان',
                'button_text' => null,
                'button_link' => null,
                'sort_order' => 2,
            ],
        ];

        foreach ($slides as $slide) {
            HeroSlide::query()->create([
                ...$slide,
                'is_active' => true,
            ]);
        }
    }
}
