<?php

namespace App\Models\Services;

use App\Exports\ContactUsExport;
use App\Models\ContactUs;
use App\Mail\ContactUsMail;
use App\Models\Enums\ExportFileTypeEnum;
use Illuminate\Support\Facades\Mail as FacadesMail;
use Mail;

class ContactUsService extends ModelService
{
    public function __construct(ContactUs $contactUs)
    {
        $this->contactUs = $contactUs;
        $this->model = $contactUs;
    }

    public static function create(string $name, string $email, string $company, string $message)
    {
        $contact = new ContactUs();
        $contact->name = $name;
        $contact->email = $email;
        $contact->company = $company;
        $contact->message = $message;

        if ($contact->save()) {
            FacadesMail::to(config('mail.notification_recipient'))->send(new ContactUsMail($contact));
            return $contact;
        }
    }

    public static function export(array $ids)
    {
        $date = date('Y-m-d');
        return (new ContactUsExport($ids))->download($date . '_contacts_us.csv');
    }
}
