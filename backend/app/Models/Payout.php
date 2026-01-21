<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Payout extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['image_url'];

    /**** Relationship ****/
    public function state() {
        return $this->belongsTo(PayoutState::class, 'payout_state_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('reference', 'LIKE', '%' . $search . '%')
                ->orWhere('amount', 'LIKE', '%' . $search . '%')
                ->orWhere('payer_alias', 'LIKE', '%' . $search . '%');
        });
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('user_id') !== null) {
            $query->where('user_id', $filters->get('user_id'));
        } else if(Auth::check() && Auth::user()->getRoleNames()[0] !== 'SuperAdmin' && Auth::user()->getRoleNames()[0] !== 'Administrator') {
            $query->where('user_id', Auth::user()->id);
        }

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('state_id') !== null) {
            $query->where('payout_state_id', $filters->get('state_id'));
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
    public static function createPayout($request) {

        $payout = self::create([
            'user_id'                           => Auth::user()->id,
            'payout_state_id'                   => $request->payout_state_id ?? 1,
            'swish_id'                          => $request->swish_id ?? null,
            'reference'                         => $request->reference ?? null,
            'amount'                            => $request->amount ?? 0,
            'payer_alias'                       => $request->payer_alias ?? null,
            'payee_alias'                       => $request->payee_alias ?? null,
            'payee_ssn'                         => $request->payee_ssn ?? null,
            'currency'                          => $request->currency ?? 'SEK',
            'payout_type'                       => $request->payout_type ?? 'PAYOUT',
            'instruction_date'                  => $request->instruction_date ?? null,
            'payout_instruction_uuid'           => $request->payout_instruction_uuid ?? null,
            'message'                           => $request->message ?? null,
            'signing_certificate_serial_number' => $request->signing_certificate_serial_number ?? null,
            'location_url'                      => $request->location_url ?? null,
            'error_message'                     => $request->error_message ?? null,
            'error_code'                        => $request->error_code ?? null
        ]);

        return $payout;
    }    
    
    public static function deletePayout($id) {
        self::deletePayouts(array($id));
    }

    public static function deletePayouts($ids) {
        foreach ($ids as $id) {
            $payout = self::find($id);
            $payout->delete();
        }
    }

    public static function cancelPayout($id) {
       $payout = self::find($id);
       $payout->payout_state_id = 3; // Cancelled
       $payout->save();
    }

    public static function sendPayout($request)
    {
        $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
        $payouts = self::with('user')->whereIn('id', $ids)->get();

        if ($payouts->isEmpty()) {
            return false;
        }

        $data = ['payouts' => $payouts];

        $subject = 'Swish-betalningskvitto';

        try {
            // Generate PDFs for each payout
            $pdfAttachments = [];
            foreach ($payouts as $payout) {
                $pathToFile = storage_path('app/public/' . $payout->image);
                $imageBase64 = null;

                if (file_exists($pathToFile)) {
                    $imageData = file_get_contents($pathToFile);
                    $mime = mime_content_type($pathToFile);
                    $imageBase64 = 'data:' . $mime . ';base64,' . base64_encode($imageData);
                }

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.payout-receipt', [
                    'payout' => $payout,
                    'imageBase64' => $imageBase64
                ]);

                $pdfAttachments[] = [
                    'pdf' => $pdf->output(),
                    'name' => 'kvitto_' . ($payout->reference ?? $payout->id) . '.pdf'
                ];
            }

            \Mail::send(
                'emails.payouts.receipt',
                $data,
                function ($message) use ($request, $pdfAttachments, $payouts, $subject) {
                    $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    $message->to($request->email)->subject($subject);

                    // Adjuntar PDFs
                    foreach ($pdfAttachments as $attachment) {
                        $message->attachData(
                            $attachment['pdf'],
                            $attachment['name'],
                            ['mime' => 'application/pdf']
                        );
                    }

                    // Adjuntar imágenes originales
                    foreach ($payouts as $payout) {
                        $pathToFile = storage_path('app/public/' . $payout->image);

                        if (file_exists($pathToFile)) {
                            $mime = mime_content_type($pathToFile);
                            $message->attach($pathToFile, [
                                'as' => \Illuminate\Support\Str::of($payout->image)->afterLast('/'),
                                'mime' => $mime
                            ]);
                        }
                    }
                }
            );

            return true;

        } catch (\Exception $e) {
            \Log::error('Error al enviar correo:', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**** Accessors ****/
    public function getImageUrlAttribute() {
        if ($this->image) {
            return url('storage/' . $this->image);
        }
        return null;
    }
}

