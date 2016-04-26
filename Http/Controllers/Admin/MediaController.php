<?php namespace Modules\Media\Http\Controllers\Admin;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Core\Http\Controllers\Admin\AdminBaseController;
use Modules\Media\Entities\File;
use Modules\Media\Http\Requests\UpdateMediaRequest;
use Modules\Media\Image\Imagy;
use Modules\Media\Image\ThumbnailsManager;
use Modules\Media\Repositories\FileRepository;
use Pingpong\Modules\Facades\Module;
use Yajra\Datatables\Datatables;

class MediaController extends AdminBaseController
{
    /**
     * @var FileRepository
     */
    private $file;
    /**
     * @var Repository
     */
    private $config;
    /**
     * @var Imagy
     */
    private $imagy;
    /**
     * @var ThumbnailsManager
     */
    private $thumbnailsManager;

    public function __construct(FileRepository $file, Repository $config, Imagy $imagy, ThumbnailsManager $thumbnailsManager)
    {
        parent::__construct();
        $this->file = $file;
        $this->config = $config;
        $this->imagy = $imagy;
        $this->thumbnailsManager = $thumbnailsManager;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, LaravelLocalization $locale, Imagy $imagy)
    {
        $columns = $request->get('columns');

        if (!empty($columns)) {
            $items = DB::table('media__files')
            ->Join('media__file_translations', function ($join) use ($locale) {
                $join->on('media__file_translations.file_id', '=', 'media__files.id');
                $join->on('media__file_translations.locale', '=', DB::raw('\''.$locale->getCurrentLocale().'\''));
            }, null, null, 'left outer')
            ->select([
                'media__files.id',
                'media__files.path',
                'media__files.filename',
                'media__file_translations.alt_attribute',
                'media__file_translations.description',
                'media__file_translations.keywords',
                'media__files.created_at',
            ]);

            return Datatables::of($items)
                ->addColumn('thumbnail', function ($file) use ($imagy) {
                    $image_extensions = ['jpg', 'png', 'jpeg', 'gif'];
                    if (in_array(pathinfo($file->path, PATHINFO_EXTENSION), $image_extensions)) {
                        return '<a href="'.$file->path.'" class="modal-link" target="_blank"><img src="'.$imagy->getThumbnail($file->path, 'smallThumb').'" alt=""/></a>';
                    } else {
                        return '<i class="fa fa-file" style="font-size: 20px;"></i>';
                    }
                })
                ->make(true);
        }
        //$files = $this->file->all();
        $this->assetPipeline->requireJs('datatables.js')->after('jquery');
        $this->assetPipeline->requireJs('datatables-bs.js')->after('datatables.js');
        $this->assetPipeline->requireCss('datatables-bs.css')->after('bootstrap');

        $this->assetManager->addAsset('bootstrap-editable.css', Module::asset('translation:vendor/x-editable/dist/bootstrap3-editable/css/bootstrap-editable.css'));
        $this->assetManager->addAsset('bootstrap-editable.js', Module::asset('translation:vendor/x-editable/dist/bootstrap3-editable/js/bootstrap-editable.min.js'));
        $this->assetPipeline->requireJs('bootstrap-editable.js');
        $this->assetPipeline->requireCss('bootstrap-editable.css');

        $config = $this->config->get('asgard.media.config');

        return view('media::admin.index', compact('files', 'config'));
    }

    public function isImage()
    {
        return in_array(pathinfo($this->path, PATHINFO_EXTENSION), $this->imageExtensions);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('media.create');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  File     $file
     * @return Response
     */
    public function edit(File $file)
    {
        $thumbnails = $this->thumbnailsManager->all();

        return view('media::admin.edit', compact('file', 'thumbnails'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  File               $file
     * @param  UpdateMediaRequest $request
     * @return Response
     */
    public function update(File $file, UpdateMediaRequest $request)
    {
        $this->file->update($file, $request->all());

        flash(trans('media::messages.file updated'));

        return redirect()->route('admin.media.media.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  File     $file
     * @internal param int $id
     * @return Response
     */
    public function destroy(File $file)
    {
        $this->imagy->deleteAllFor($file);
        $this->file->destroy($file);

        flash(trans('media::messages.file deleted'));

        return redirect()->route('admin.media.media.index');
    }
}
