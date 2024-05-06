<?php

namespace App\Models\Services;

use App\Interfaces\ImageableInterface;
use App\Models\Image;
use Illuminate\Http\UploadedFile;

class ImageService extends ModelService
{
    /**
     * @var Image
     */
    private $image;

    /**
     * FileService constructor.
     * @param Image $image
     */
    public function __construct(Image $image)
    {
        $this->image = $image;
        $this->model = $image;
    }

    public static function create(
        ImageableInterface $imageable,
        UploadedFile $file,
        string $filename
    )
    {
        $extension = $file->getClientOriginalExtension();
        if (!$extension) {
            $extension = explode('/', $file->getMimeType())[1];
        }

        $imageable->image()->create([
            'name' => $filename,
            'extension' => $extension,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'account_id' => $imageable->account_id,
        ]);

        return $imageable->image;
    }

    public static function createEx(
        ImageableInterface $imageable,
        array $fileInfo
    )
    {
        $imageable->image()->create([
            'name' => $fileInfo['name'],
            'extension' => $fileInfo['extension'],
            'mime' => $fileInfo['mime'],
            'account_id' => $imageable->account_id,
        ]);

        return $imageable->image;
    }

    public static function updateOrCreate(
        ImageableInterface $imageable,
        UploadedFile $file,
        string $filename
    )
    {
        $imageable->image()->updateOrCreate([
            'name' => $filename,
            'extension' => $file->getClientOriginalExtension(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'account_id' => $imageable->account_id,
        ]);

        return $imageable->image;
    }

    public function setAsFeatured(bool $is_featured)
    {
        $this->image->is_featured = $is_featured;
        $this->image->save();
    }
}
