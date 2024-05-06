<?php

namespace App\Models\Services;

use App\Models\GoogleCustomer;
use Illuminate\Support\Facades\Auth;

class GoogleCustomerModelService extends ModelService
{
   private $googleCustomer;

    public function __construct(GoogleCustomer $googleCustomer)
    {
        $this->googleCustomer = $googleCustomer;
        $this->model = $googleCustomer; // required
    }

    public static function create(
        string $name,
        string $customer_id,
        int $google_account,
        int $status
    )
    {
        try {
            $googleCustomer = new googleCustomer();
            $googleCustomer->name = $name;
            $googleCustomer->customer_id = $customer_id;
            $googleCustomer->google_account = $google_account;
            $googleCustomer->status = $status;
            $googleCustomer->user_id = Auth::user()->id;
            $googleCustomer->account_id = Auth::user()->account_id;
            $googleCustomer->save();

            return $googleCustomer;
        } catch (\Throwable $th) {
            return ['error' => $th->getErrorUserMessage() ?? $th->getMessage()];
        }
    }

    public function update(
        string $name,
        string $customer_id,
        int $google_account,
        int $status
    )
    {
        try {
            $this->googleCustomer->name = $name;
            $this->googleCustomer->customer_id = $customer_id;
            $this->googleCustomer->google_account = $google_account;
            $this->googleCustomer->status = $status;
            $this->googleCustomer->save();

            return $this->googleCustomer->fresh();
        } catch (\Throwable $th) {
            return ['error' => $th->getErrorUserMessage() ?? $th->getMessage()];
        }
    }

    public function delete(): bool
    {
        if ($this->googleCustomer->campaign->count() > 0) {
            abort('403', 'Cannot delete Customer if campaign are still available');
        }

        return parent::delete();
    }
}
