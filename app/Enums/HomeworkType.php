<?php

namespace App\Enums;

enum HomeworkType: string
{
    case Text = 'text';
    case File = 'file';
    case Link = 'link';
    case Checklist = 'checklist';
}
