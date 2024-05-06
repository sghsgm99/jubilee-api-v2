<?php

namespace App\Models\Services\Factories;

use App\Interfaces\ImageableInterface;
use App\Models\Enums\StorageDiskEnum;
use App\Services\FileService;

class FileServiceFactory
{
    public static function resolve(ImageableInterface $imageable, StorageDiskEnum $enum, string $dir = null)
    {
        switch ($enum) {
            case StorageDiskEnum::PUBLIC_DO():
            case StorageDiskEnum::LOCAL(): return FileService::resolve($imageable, $enum, $dir);
        }

        throw new \InvalidArgumentException('This storage is not available in the system.');
    }
}
