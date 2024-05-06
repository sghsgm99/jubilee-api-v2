<?php

namespace App\Models\Services;

use App\Models\BuilderPage;
use App\Models\BuilderSite;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BuilderPageService extends ModelService
{
    /**
     * @var BuilderPage
     */
    private $builderPage;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    public function __construct(BuilderPage $builderPage)
    {
        $this->builderPage = $builderPage;
        $this->model = $builderPage; // required
        $this->fileSystem = Storage::disk('s3');
    }

    public static function create(
        BuilderSite $builderSite,
        string $title,
        string $slug
    ): BuilderPage
    {
        $builderPage = new BuilderPage();
        $builderPage->builder_site_id = $builderSite->id;
        $builderPage->title = $title;
        $builderPage->slug = Str::slug($slug);
        $builderPage->account_id = auth()->user()->account_id;
        $builderPage->save();

//        try {
//            $builderPage->Service()->generateHtmlAndStylingFiles();
//        } catch (\Exception $exception) {
//            abort(422, 'Page was created but files were not generated.');
//        }

        return $builderPage;
    }

    public function update(
        string $title,
        string $slug,
        string $html = null,
        string $styling = null,
        string $seo = null
    ): BuilderPage
    {
        $this->builderPage->title = $title;
        $this->builderPage->slug = Str::slug($slug);
        $this->builderPage->html = $html;
        $this->builderPage->styling = $styling;
        $this->builderPage->seo = $seo;
        $this->builderPage->save();

//        try {
//            $this->generateHtmlAndStylingFiles();
//        } catch (\Exception $exception) {
//            abort(422, 'Page was updated but files were not generated.');
//        }

        return $this->builderPage->fresh();
    }

    public function delete(): bool
    {
        try {
            $this->fileSystem->delete([
                $this->builderPage->html_file_path,
                $this->builderPage->css_file_path,
            ]);
        } catch (\Exception $exception) {
            // do nothing
        }

        return parent::delete();
    }

    public function updateOrder($order = 0)
    {
        $this->builderPage->order = $order;
        $this->builderPage->save();
    }

    public function generateHtmlAndStylingFiles(): array
    {
        $options = 'public';

        $this->fileSystem->put($this->builderPage->html_file_path, $this->builderPage->html, $options);

        $this->fileSystem->put($this->builderPage->css_file_path, $this->builderPage->styling, $options);

        return $this->builderPage->getPhysicalFiles();
    }
}
