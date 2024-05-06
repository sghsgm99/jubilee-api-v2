<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateContactUsRequest;
use App\Http\Requests\ExportContactUsRequest;
use App\Http\Resources\ContactUsResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\ContactUs;
use App\Models\Services\ContactUsService;
use App\Services\ResponseService;

class ContactUsController extends Controller
{
    public static function unguardedRoutes()
    {
        Route::post('/contact-us', [ContactUsController::class, 'create']);
    }

    public static function apiRoutes()
    {
        Route::get('/contact-us', [ContactUsController::class, 'collection']);
        Route::get('/contact-us/{contactus}', [ContactUsController::class, 'get']);
        Route::post('/contact-us/export', [ContactUsController::class, 'export']);
        Route::delete('/contact-us/{contactus}', [ContactUsController::class, 'destroy']);
    }

    public function create(CreateContactUsRequest $request)
    {
        $contactUs = ContactUsService::create(
            $request->validated()['name'],
            $request->validated()['email'],
            $request->validated()['company'],
            $request->validated()['message']
        );

        return ResponseService::success("Message has been sent successfully.", $contactUs);

    }

    public function collection(Request $request)
    {
        return ContactUsResource::collection(ContactUs::paginate($request->input('per_page', 10)));
    }

    public function get(ContactUs $contactus)
    {
        return ResponseService::success('Success', new ContactUsResource($contactus));
    }

    public function export(ExportContactUsRequest $request)
    {
        return ContactUsService::export(
            $request->validated()['ids']
        );
    }

    public function destroy(ContactUs $contactus)
    {
        return ResponseService::success('Contact us message deleted succesfully.', $contactus->delete());
    }
}
