<?php

namespace App\Support;

use Illuminate\Foundation\Console\ServeCommand;

class DevServeCommand extends ServeCommand
{
    /**
     * PHP's built-in server parses multipart uploads before any app code runs.
     * On Windows (Herd / artisan serve) an empty upload_tmp_dir often fails
     * with "unable to create a temporary file".
     */
    protected function serverCommand()
    {
        $tmp = $this->uploadTempDirectory();

        $command = parent::serverCommand();
        array_splice($command, 1, 0, [
            '-d', 'upload_tmp_dir='.$tmp,
            '-d', 'sys_temp_dir='.$tmp,
            '-d', 'upload_max_filesize=50M',
            '-d', 'post_max_size=50M',
        ]);

        return $command;
    }

    protected function uploadTempDirectory(): string
    {
        $candidates = [
            storage_path('app/tmp'),
            rtrim(sys_get_temp_dir(), '\\/').DIRECTORY_SEPARATOR.'ebraz-php-uploads',
        ];

        foreach ($candidates as $path) {
            if (! is_dir($path) && ! @mkdir($path, 0777, true) && ! is_dir($path)) {
                continue;
            }

            if (is_writable($path)) {
                return $path;
            }
        }

        return $candidates[0];
    }
}
