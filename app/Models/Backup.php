<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    use HasUuids;

    protected $fillable = [
        'type',
        'file_path',
        'file_url',
    ];
}
