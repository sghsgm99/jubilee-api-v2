<?php

namespace App\Traits;

use App\Models\Image;
use App\Models\Services\ImageService;
use Illuminate\Http\UploadedFile;

trait ImageModelServiceTrait
{
    public function attachImage(UploadedFile $image, ?string $filename)
    {
        if (!$filename) {
            $name = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
            $extension = $image->getClientOriginalExtension();
            $filename = "{$name}.{$extension}";
        }

        return ImageService::create($this->model, $image, $filename);
    }

    public function attachImageEx(array $fileInfo)
    {
        return ImageService::createEx($this->model, $fileInfo);
    }

    public function cloneImages($newModel)
    {
        if ($this->model->external_sync_image) {
            $newModel->external_sync_image = $this->model->external_sync_image;
            $newModel->save();
        }

        if ($this->model->images) {
            $this->model->FileServiceFactory()->cloneFiles($newModel->getRootDestinationPath());

            $this->model->images->each(function($item, $key) use ($newModel) {
                $duplicate = $item->replicate()->fill([
                    'imageable_id' => $newModel->id,
                    'imageable_type' => $newModel->class_name,
                ]);
                $duplicate->save();
            });
        }
    }

    public function detachImage(int $image_id)
    {
        $image = $this->model->image->whereId($image_id)->first();

        if (!$image) {
            return true;
        }

        $this->deleteImage($image);
    }

    public function deleteImage(Image $image, string $dir = null)
    {
        $this->model->FileServiceFactory($dir)->deleteFile($image->name);

        // force delete coz we also remove the physical file
        $image->Service()->forceDelete();
    }

    public function markAsFeatured(int $image_id)
    {
        foreach ($this->model->images as $image) {
            if ($image->id === $image_id) {
                $image->Service()->setAsFeatured(true);
                continue;
            }

            $image->Service()->setAsFeatured(false);
        }
    }
}
