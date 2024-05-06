<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Models\GoogleCustomer;
use App\Models\Services\GoogleCustomerModelService;
use App\Http\Resources\GoogleCustomerResource;
use App\Services\ResponseService;

class GoogleCustomerController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('google-customers', [GoogleCustomerController::class, 'getCollection']);
        Route::post('google-customers', [GoogleCustomerController::class, 'create']);
        Route::put('google-customers/{googleCustomer}', [GoogleCustomerController::class, 'update']);
        Route::get('google-customers/{googleCustomer}', [GoogleCustomerController::class, 'getSingle']);
        Route::delete('google-customers/{googleCustomer}', [GoogleCustomerController::class, 'delete']);
        Route::get('google-customers/google/allAccounts', [GoogleCustomerController::class, 'getGoogleAccounts']);
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');
        $google_account = $request->input('google_account', null);
        $status = $request->input('status', null);
        
        $customers = GoogleCustomer::search(
            $search,
            $sort,
            $sort_type,
            $google_account,
            $status
        )->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

        return GoogleCustomerResource::collection($customers);
    }

    public function create(Request $request)
    {
        $customer = GoogleCustomerModelService::create(
            $request->input('name'),
            $request->input('customer_id'),
            $request->input('google_account'),
            $request->input('status')
        );

        if (isset($customer['error'])) {
            return ResponseService::clientError('Customer was not created.', $customer);
        }

        return ResponseService::successCreate('Customer was created.', new GoogleCustomerResource($customer));
    }
    
    public function update(GoogleCustomer $googleCustomer, Request $request)
    {
        $customer = $googleCustomer->Service()->update(
            $request->input('name'),
            $request->input('customer_id'),
            $request->input('google_account'),
            $request->input('status')
        );

        if (isset($customer['error'])) {
            return ResponseService::clientError('Customer was not updated.', $customer);
        }

        return ResponseService::successCreate('Customer was updated.', new GoogleCustomerResource($customer));
    }

    public function getSingle(GoogleCustomer $googleCustomer)
    {
        return new GoogleCustomerResource($googleCustomer);
    }

    public function delete(GoogleCustomer $googleCustomer)
    {
        $googleCustomer->Service()->delete();

        return ResponseService::successCreate('Customer was deleted successfully.');
    }

    public function getGoogleAccounts(Request $request)
    {
        return config('google.account');
    }
}
