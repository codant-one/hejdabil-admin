<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Setting extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id')->withTrashed();
    }

    public function color() {
        return $this->belongsTo(SettingColor::class, 'setting_color_id', 'id');
    }

    public function billing() {
        return $this->belongsTo(SettingBilling::class, 'setting_billing_id', 'id');
    }

    public function agreement() {
        return $this->belongsTo(SettingAgreement::class, 'setting_agreement_id', 'id');
    }

    /**** Public methods ****/
    public static function colors($request, $settings) {

        $supplier_id = self::resolveSupplierId($request);

        $settings->query()->updateOrCreate([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
        ], [
            'setting_color_id' => $request->setting_color_id === 'null' ? null : $request->setting_color_id,
            'setting_billing_id' => $request->setting_billing_id === 'null' ? null : $request->setting_billing_id,
            'setting_agreement_id' => $request->setting_agreement_id === 'null' ? null : $request->setting_agreement_id,
            'primary_color' => $request->primary_color === 'null' ? null : $request->primary_color,
            'secondary_color' => $request->secondary_color === 'null' ? null : $request->secondary_color
        ]);

        return $settings;
    }

    private static function resolveSupplierId($request) {
        if (!Auth::check()) {
            return $request->supplier_id === 'null' ? null : $request->supplier_id;
        }

        $user = Auth::user();
        $roles = $user->getRoleNames();
        $role = $roles[0] ?? null;

        return match (true) {
            $role === 'Supplier' => $user->supplier->id,
            $role === 'User' => $user->supplier->boss_id,
            $request->supplier_id === 'null' => null,
            default => $request->supplier_id,
        };
    }
}