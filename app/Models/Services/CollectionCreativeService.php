<?php

namespace App\Models\Services;

use App\Traits\ImageModelServiceTrait;
use App\Models\CollectionCreative;
use App\Models\Enums\CollectionCreativeTypeEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CollectionCreativeService extends ModelService
{
    use ImageModelServiceTrait;

    private $creative;

    public function __construct(CollectionCreative $creative)
    {
        $this->creative = $creative;
        $this->model = $creative;
    }

    public static function create(
        array $data = [],
        CollectionCreativeTypeEnum $type
    ) {
        $creative = new CollectionCreative();

        $creative->data = $data;
        $creative->type = $type;
        $creative->account()->associate(Auth()->user()->account);
        $creative->save();

        $image = null;
        if(count($data['image']) > 0) {
            $image = $data['image'][0]['src'] ?? $data['image'][0];
        }

        if($image) {
            $info = pathinfo($image);
            $contents = file_get_contents($image);
            $file = '/tmp/' . $info['basename'];
            file_put_contents($file, $contents);
            $uploaded_file = new UploadedFile($file, $info['basename']);
            $file = $creative->FileServiceFactory()->uploadFile($uploaded_file, 'creative-image-'.$creative->id);
            $creative->Service()->attachImage($uploaded_file, $file['name']);
        }

        return $creative;
    }

    public static function createUpload(
        UploadedFile $image = null,
        CollectionCreativeTypeEnum $type
    ) {
        try {
            DB::beginTransaction();

            $creative = new CollectionCreative();

            $creative->data = null;
            $creative->type = $type;
            $creative->account()->associate(Auth()->user()->account);
            $creative->save();

            if ($image) {
                $filename = 'image_'.$creative->id;
                $file = $creative->FileServiceFactory()->uploadFile($image, $filename);
                $creative->Service()->attachImage($image, $file['name']);
            }

            DB::commit();

            return $creative;
        } catch (\Throwable $th) {
            DB::rollBack();
            
            return [
                'error' => true,
                'message' => $th->getMessage(),
            ];
        }
    }

    public function uploadImage()
    {
        $image = null;
        if(count($this->creative->data['image']) > 0) {
            $image = $this->creative->data['image'][0]['src'] ?? $this->creative->data['image'][0];
        }
        
        if($image) {
            $info = pathinfo($image);
            $contents = file_get_contents($image);
            $file = '/tmp/' . $info['basename'];
            file_put_contents($file, $contents);
            $uploaded_file = new UploadedFile($file, $info['basename']);
            $file = $this->creative->FileServiceFactory()->uploadFile($uploaded_file, 'creative-image-'.$this->creative->id);
            $this->creative->Service()->attachImage($uploaded_file, $file['name']);
        }

        return $this->creative;
    }

    public static function deleteCreatives(array $creative_ids)
    {
        foreach ($creative_ids as $creative_id) {
            if($creative = CollectionCreative::find($creative_id)) {
                $creative->Service()->delete();
            }
        }
    }

    public function delete(): bool
    {
        if($this->creative->image) {
            $this->creative->FileServiceFactory()->deleteFile($this->creative->image->name);
        }

        return $this->creative->delete();
    }
}
