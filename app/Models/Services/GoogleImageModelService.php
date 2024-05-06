<?php

namespace App\Models\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use App\Models\GoogleAd;
use App\Models\GoogleAdgroup;
use App\Services\GoogleAdService;
use App\Traits\ImageModelServiceTrait;
use App\Jobs\ProcessGoogleAd;
use App\Models\Enums\GoogleCampaignStatusEnum;
use App\Models\GoogleImage;
use Intervention\Image\Facades\Image;

class GoogleImageModelService extends ModelService
{
    use ImageModelServiceTrait;

    private $googleImage;

    public function __construct(GoogleImage $googleImage)
    {
        $this->googleImage = $googleImage;
        $this->model = $googleImage; // required
    }

    public static function createUpload(
        UploadedFile $image = null,
        int $type
    ) {
        try {
            DB::beginTransaction();

            $googleImage = new GoogleImage();

            $googleImage->data = null;
            $googleImage->type = $type;
            $googleImage->user_id = Auth::user()->id;
            $googleImage->account_id = Auth::user()->account_id;
            $googleImage->save();

            if ($image) {
                $filename = 'image_'.$googleImage->id;
                $file = $googleImage->FileServiceFactory()->uploadFile($image, $filename);
                $googleImage->Service()->attachImage($image, $file['name']);
            }

            DB::commit();

            return $googleImage;
        } catch (\Throwable $th) {
            DB::rollBack();
            
            return [
                'error' => true,
                'message' => $th->getMessage(),
            ];
        }
    }

    public static function createUploadAI(
        string $imageUrl,
        int $type
    ) {
        try {
            DB::beginTransaction();

            $googleImage = new GoogleImage();
            $googleImage->data = null;
            $googleImage->type = $type;
            $googleImage->user_id = Auth::user()->id;
            $googleImage->account_id = Auth::user()->account_id;
            $googleImage->save();

            $filename = 'image'.$googleImage->id;
            if ($type == 1)
                $fileInfo = $googleImage->FileServiceFactory()->uploadResizeImage($imageUrl, $filename, 600, 314);
            else
                $fileInfo = $googleImage->FileServiceFactory()->uploadImage($imageUrl, $filename);
            $googleImage->Service()->attachImageEx($fileInfo);
            
            DB::commit();

            return $googleImage;
        } catch (\Throwable $th) {
            DB::rollBack();
            
            return [
                'error' => true,
                'message' => $th->getMessage(),
            ];
        }
    }
}
