<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetails extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    
    protected $fillable = [
        'user_id',
        'company',
        'organization_number',
        'address',
        'street',
        'postal_code',
        'phone',
        'bank',
        'account_number',
        'iban',
        'iban_number',
        'bic',
        'plus_spin',
        'swish',
        'vat',
        'logo'
    ];

    /**** Relationship ****/
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

     /**** Public methods ****/
    public static function updateOrCreateUser($request, $user) {
        $userD = UserDetails::updateOrCreate(
            [    'user_id' => $user->id ],
            [
                'company' => $request->company,
                'organization_number' => $request->organization_number,
                'link' => $request->link,
                'address' => $request->address,
                'street' => $request->street,
                'postal_code' => $request->postal_code,
                'phone' => $request->phone,
                'bank' => $request->bank,
                'account_number' => $request->account_number,
                'iban' => $request->iban === 'null' ? null : $request->iban,
                'iban_number' => $request->iban_number === 'null' ? null : $request->iban_number,
                'bic' => $request->bic === 'null' ? null : $request->bic,
                'plus_spin' => $request->plus_spin === 'null' ? null : $request->plus_spin,
                'swish' => $request->swish === 'null' ? null : $request->swish,
                'vat' => $request->vat === 'null' ? null : $request->vat
            ]
        );

        return $userD;
    }

    public static function updateOrCreatePhone($request, $user) {
        $userD = UserDetails::updateOrCreate(
            [    'user_id' => $user->id ],
            [
                'phone' => $request->phone
            ]
        );

        return $userD;
    }
}
