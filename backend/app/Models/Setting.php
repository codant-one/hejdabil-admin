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

        $settings = self::query()->updateOrCreate([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
        ], [
            'setting_color_id' => self::resolveOptionalField($request, 'setting_color_id', $settings->setting_color_id ?? null),
            'setting_billing_id' => null,
            'setting_agreement_id' => null,
            'primary_color' => self::resolveOptionalField($request, 'primary_color', $settings->primary_color ?? null),
            'secondary_color' => self::resolveOptionalField($request, 'secondary_color', $settings->secondary_color ?? null)
        ]);

        return $settings;
    }

    private static function resolveOptionalField($request, $key, $fallback = null) {
        if (!$request->exists($key)) {
            return $fallback;
        }

        $value = $request->input($key);

        return $value === 'null' ? null : $value;
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