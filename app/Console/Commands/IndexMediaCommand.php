<?php

namespace App\Console\Commands;

use App\Services\FileService;
use Illuminate\Console\Command;

class IndexMediaCommand extends Command
{
    protected $signature = 'media:index {--disk=public : Filesystem disk to scan}';

    protected $description = 'Index existing storage files into the media catalog';

    public function handle(FileService $files): int
    {
        $disk = (string) $this->option('disk');
        $created = $files->indexDisk($disk);

        $this->info("Indexed {$created} new file(s) from the [{$disk}] disk.");

        return self::SUCCESS;
    }
}
