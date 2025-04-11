<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;
use PDF;

use App\Models\Invoice;
use App\Models\Supplier;

class Billing extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**** Relationship ****/
    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id')->withTrashed();
    }

    public function client() {
        return $this->belongsTo(Client::class, 'client_id', 'id')->withTrashed();
    }

    public function state() {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function($query) use ($search) {
            $query->whereHas('client', function ($q) use ($search) {
                $q->withTrashed()
                  ->where(function ($query) use ($search) {
                      $query->where('fullname', 'LIKE', '%' . $search . '%');
                  });
            })
            ->orWhereHas('supplier', function ($q) use ($search) {
                $q->withTrashed()
                  ->whereHas('user', function ($q) use ($search) {
                      $q->withTrashed()
                        ->where(function ($query) use ($search) {
                            $query->where('name', 'LIKE', '%' . $search . '%')
                                  ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                                  ->orWhere('email', 'LIKE', '%' . $search . '%');
                        });
                  });
            })
            ->orWhere('invoice_id', 'LIKE', '%' . $search . '%')
            ->orWhere('detail', 'LIKE', '%' . $search . '%')
            ->orWhere('notes', 'LIKE', '%' . $search . '%');
        });
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if(Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier') {
            $query->where('supplier_id', Auth::user()->supplier->id);
        }

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('supplier_id') !== null) {
            $query->where('supplier_id', $filters->get('supplier_id'));
        }

        if ($filters->get('client_id') !== null) {
            $query->where('client_id', $filters->get('client_id'));
        }

        if ($filters->get('state_id') !== null) {
            $query->where('state_id', $filters->get('state_id'));
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'order_id';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'asc';
            $query->whereOrder($field, $orderBy);
        }
    }

    public function scopePaginateData($query, $limit) {
        if ($limit == 'all') {
            return collect(['data' => $query->get()]);
        }

        return $query->paginate($limit);
    }

    /**** Public methods ****/
    public static function createBilling($request) {

        $details = [];
        $groupedNotes = [];

        foreach($request->details as $item) {
            $array = [];
            foreach(json_decode($item, true) as $key => $value) {
                $aux = [];
                $aux['id'] = $key;
                $aux['value'] = $key === 3 || $key === 4 ? number_format($value, 2, '.', '') : $value;

                array_push($array, $aux);
            }
            array_push($details, $array);
        }        

        if(isset($request->notes)) {
            foreach ($request->notes as $item) {
                $decodedItem = json_decode($item, true);
                
                $id = $decodedItem['id'];
                $note = $decodedItem['note'];
            
                if (!isset($groupedNotes[$id])) {
                    $groupedNotes[$id] = [];
                }

                $groupedNotes[$id][] = $note;
            }
        }

        $isSupplier = Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier';

        $billing = self::create([
            'supplier_id' => $request->supplier_id === 'null' ? ($isSupplier ? Auth::user()->supplier->id : null) : $request->supplier_id,
            'client_id' =>  $request->client_id,
            'invoice_id' =>  $request->invoice_id,
            'invoice_date' =>  $request->invoice_date,
            'due_date' =>  $request->due_date,
            'payment_terms' =>  $request->payment_terms . ' days net',
            'reference' => $request->reference === 'null' ? null : $request->reference,
            'subtotal' =>  $request->subtotal,
            'tax' =>  $request->tax,
            'total' =>  $request->total,
            'detail' => json_encode($details, true),
            'notes' => isset($request->notes) ? json_encode(array_values($groupedNotes)) : null
        ]);

        $date = Carbon::now()->timestamp;
        $types = Invoice::all();
        $details = json_decode($billing->detail, true);
        $notes = json_decode($billing->notes, true);

        foreach($details as $row)
            $invoices[] = $row;

        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755,true);
        } //create a folder

        PDF::loadView('pdfs.invoice', compact('billing', 'types', 'invoices', 'notes'))->save(storage_path('app/public/pdfs').'/'.Str::slug($billing->client_id).'-'.$date.'.pdf');

        $billing->file = 'pdfs/'.Str::slug($billing->client_id).'-'.$date.'.pdf';
        $billing->update();

        return $billing;
    }

    public static function updateBilling($request, $billing) {

        $details = [];
        $groupedNotes = [];

        foreach($request->details as $item) {
            $array = [];
            foreach(json_decode($item, true) as $key => $value) {
                $aux = [];
                $aux['id'] = $key;
                $aux['value'] = $key === 3 || $key === 4 ? number_format($value, 2, '.', '') : $value;

                array_push($array, $aux);
            }
            array_push($details, $array);
        }

        if(isset($request->notes)) {
            foreach ($request->notes as $item) {
                $decodedItem = json_decode($item, true);
                
                $id = $decodedItem['id'];
                $note = $decodedItem['note'];
            
                if (!isset($groupedNotes[$id])) {
                    $groupedNotes[$id] = [];
                }

                $groupedNotes[$id][] = $note;
            }
        }

        $isSupplier = Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier';

        $billing->update([
            'supplier_id' => $request->supplier_id === 'null' ? ($isSupplier ? Auth::user()->supplier->id : null) : $request->supplier_id,
            'client_id' =>  $request->client_id,
            'invoice_id' =>  $request->invoice_id,
            'invoice_date' =>  $request->invoice_date,
            'due_date' =>  $request->due_date,
            'payment_terms' =>  $request->payment_terms . ' days net',
            'reference' => $request->reference === 'null' ? null : $request->reference,
            'subtotal' =>  $request->subtotal,
            'tax' =>  $request->tax,
            'total' =>  $request->total,
            'detail' => json_encode($details, true),
            'notes' => isset($request->notes) ? json_encode(array_values($groupedNotes)) : null
        ]);

        $billing = self::find($billing->id);
        $date = Carbon::now()->timestamp;
        $types = Invoice::all();
        $details = json_decode($billing->detail, true);
        $notes = json_decode($billing->notes, true);

        foreach($details as $row)
            $invoices[] = $row;

        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755,true);
        } //create a folder

        PDF::loadView('pdfs.invoice', compact('billing', 'types', 'invoices', 'notes'))->save(storage_path('app/public/pdfs').'/'.Str::slug($billing->client_id).'-'.$date.'.pdf');

        $billing->file = 'pdfs/'.Str::slug($billing->client_id).'-'.$date.'.pdf';
        $billing->update();

        return $billing;
    }

    public static function createCredit($billing) {

        $isSupplier = Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier';

        if($isSupplier) {
            $supplier = Supplier::with(['billings'])->where('user_id', Auth::user()->id)->first();
            $invoice_id = count($supplier->billings) + 1;
        } else {
            $invoice_id = self::whereNull('supplier_id')->count() + 1;
        }

        $array = json_decode($billing->detail, true);

        foreach ($array[0] as &$item) {
            if ($item['id'] == 3 || $item['id'] == 4) {
                $numericValue = is_numeric($item['value']) ? (float)$item['value'] : null;
                if ($numericValue !== null) {
                    $item['value'] = '-' . ltrim($item['value'], '-'); // Agrega signo negativo (o lo mantiene si ya lo tiene)
                }
            }
        }

        $billing = self::create([
            'supplier_id' => $billing->supplier_id,
            'client_id' =>  $billing->client_id,
            'state_id' => 9,
            'invoice_id' =>  $invoice_id,
            'invoice_date' => now(),
            'due_date' =>  now(),
            'payment_terms' =>  '0 days net',
            'reference' => $billing->reference,
            'subtotal' => '-' . $billing->subtotal,
            'tax' =>  '-' . $billing->tax,
            'total' =>  '-' . $billing->total,
            'detail' => json_encode($array, true),
            'notes' => $billing->notes
        ]);

        $date = Carbon::now()->timestamp;
        $types = Invoice::all();
        $details = json_decode($billing->detail, true);
        $notes = json_decode($billing->notes, true);

        foreach($details as $row)
            $invoices[] = $row;

        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755,true);
        } //create a folder

        PDF::loadView('pdfs.invoice', compact('billing', 'types', 'invoices', 'notes'))->save(storage_path('app/public/pdfs').'/'.Str::slug($billing->client_id).'-'.$date.'.pdf');

        $billing->file = 'pdfs/'.Str::slug($billing->client_id).'-'.$date.'.pdf';
        $billing->update();

        return $billing;
    }

    public static function deleteBilling($id) {
        self::deleteBillings(array($id));
    }

    public static function deleteBillings($ids) {
        foreach ($ids as $id) {
            $billing = self::find($id);
            $billing->delete();
        }
    }

    public static function sendMail($billing) {
      
        $billing = self::with(['client', 'supplier.user'])->find($billing->id);

        $data = [
            'user' => $billing->client->fullname,
            'text' => 'We hope this message finds you well. <br> Please be advised that we have generated a new invoice in your name with the following details:',
            'billing' => $billing,
            'text_info' => 'Please find attached the invoice in PDF format. You can download and review it at any time. <br> If you have any questions or need more information, please do not hesitate to contact us.',
            'buttonText' => 'Download',
            'pdfFile' => asset('storage/'.$billing->file)
        ];

        $clientEmail = $billing->client->email;
        $subject = 'Your invoice #'. $billing->invoice_id . ' is available';
            
        try {
            \Mail::send(
                'emails.invoices.notifications'
                , $data
                , function ($message) use ($clientEmail, $subject) {
                    $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    $message->to($clientEmail)->subject($subject);
            });

            return true;
        } catch (\Exception $e){
            Log::info("Error mail => ". $e);
            return false;
        }
        
         
    }
}
