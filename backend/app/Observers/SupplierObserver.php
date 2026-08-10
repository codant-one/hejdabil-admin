<?php

namespace App\Observers;

use App\Models\Supplier;
use Carbon\Carbon;

class SupplierObserver
{
    public function saving(Supplier $supplier): void
    {
        if (
            $supplier->isDirty('start_date') && 
            $supplier->start_date && 
            $supplier->next_billing_date === null
        ) {
            $supplier->next_billing_date = Carbon::parse($supplier->start_date)->copy()->addDays(5);
        }
    }
}
