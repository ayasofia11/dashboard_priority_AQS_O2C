<?php
// app/Enums/ImportStatus.php
namespace App\Enums;

enum ImportStatus: string
{
    case Processing = 'PROCESSING';
    case Completed  = 'COMPLETED';
    case Failed     = 'FAILED';
}
