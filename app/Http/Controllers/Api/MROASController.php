<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMroasRequest;
use App\Http\Requests\CreateOcodesRequest;
use App\Http\Requests\ImportOcodesRequest;
use App\Http\Resources\MROASResource;
use App\Http\Resources\OcodesResource;
use App\Models\Mroas;
use App\Models\Ocodes;
use App\Models\Site;
use App\Models\Services\MROASService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Imports\OcodesImport;
use Maatwebsite\Excel\Facades\Excel;

class MROASController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('/mroas/ocodes', [MROASController::class, 'createOcodes']);
        ROute::post('/mroas/ocodes/import', [MROASController::class, 'importOcodes']);
        Route::get('/mroas/ocodes/global-search', [MROASController::class, 'collectionOcodes']);
        Route::get('/mroas/ocodes/{ocodes:id}', [MROASController::class, 'getOcodes']);
        Route::put('/mroas/ocodes/{ocodes:id}', [MROASController::class, 'updateOcodes']);
        Route::delete('/mroas/ocodes/delete', [MROASController::class, 'deleteOcodes']);

        Route::get('/mroas', [MROASController::class, 'collection']);
        Route::get('/mroas/{mroas:id}', [MROASController::class, 'get']);
    }

    public static function unguardedRoutes()
    {
        Route::post('/mroas/create', [MROASController::class, 'create']);
    }

    public function create(CreateMroasRequest $request)
    {
        $mroasData = MROASService::create(
            $request->validated()['cid'],
            $request->validated()['intl'],
            $request->validated()['keyword']
        );

        return $mroasData;
    }

    public function collection(Request $request)
    {
        return MROASResource::collection(Mroas::paginate($request->input('per_page', 10)));
    }

    public function get(Mroas $mroas)
    {
        return ResponseService::success('Success', new MROASResource($mroas));
    }

    public function createOcodes(CreateOcodesRequest $request)
    {
        $title = "Adsense " . $request->validated()['client_id'];
        $ocodes = MROASService::createOcodes(
            $title,
            $request->validated()['ocode'],
            $request->validated()['client_id'],
            Site::findOrFail($request->validated()['site_id'])
        );

        return $ocodes;
    }

    public function collectionOcodes(Request $request)
    {
        return MROASService::gloabalSearch($request->search, $request->input('per_page', 10));;
    }

    public function getOcodes(Ocodes $ocodes)
    {
        return ResponseService::success('Success.', new OcodesResource($ocodes));
    }

    public function updateOcodes(Ocodes $ocodes, CreateOcodesRequest $request)
    {
        return $ocodes->Service()->updateOcodes(
            $request->validated()['ocode'],
            $request->validated()['client_id'],
            Site::findOrFail($request->validated()['site_id'])
        );
    }

    public function deleteOcodes(Request $request)
    {
        return MROASService::bulkDelete($request['ids']);
    }

    public function importOcodes(ImportOcodesRequest $request)
    {
        $array = Excel::toArray(new OcodesImport, $request->validated()['ocode_import_file']);
        return MROASService::createBulkOcodes($array, $request->validated()['user_id']);
    }
}
