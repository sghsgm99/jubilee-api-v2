<?php

namespace App\Models\Enums;

/**
 * @method static XLSX()
 * @method static CSV()
 */
class ExportFileTypeEnum extends Enumerate
{
    const XLSX = 1;
    const CSV = 2;
}
