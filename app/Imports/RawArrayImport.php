<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class RawArrayImport implements ToArray
{
    public function array(array $array): void
    {
        // rien à faire ici
    }
}
