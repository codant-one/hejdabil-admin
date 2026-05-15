<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use App\Models\SettingBilling;
use App\Models\SettingAgreement;
use App\Models\SettingNotification;

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

    public function notification() {
        return $this->belongsTo(SettingNotification::class, 'setting_notification_id', 'id');
    }

    /**** Public methods ****/
    public static function colors($request, $settings) {

        $supplier_id = self::resolveSupplierId($request);

        $settings = self::query()->updateOrCreate([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
        ], [
            'setting_color_id' => self::resolveOptionalField($request, 'setting_color_id', $settings->setting_color_id ?? null),
            'primary_color' => self::resolveOptionalField($request, 'primary_color', $settings->primary_color ?? null),
            'secondary_color' => self::resolveOptionalField($request, 'secondary_color', $settings->secondary_color ?? null),
            'theme' => self::resolveOptionalField($request, 'theme', $settings->theme ?? 0),
        ]);

        return $settings;
    }

    public static function billings($request, $settings) {

        $supplier_id = self::resolveSupplierId($request);

        $currentSettingBilling = $settings?->billing;
        $billingId = self::resolveOptionalField($request, 'billing_id', $settings->setting_billing_id ?? null);

        $settingBilling = SettingBilling::query()->updateOrCreate([
            'id' => $billingId,
        ], [
            'type' => self::resolveOptionalField($request, 'type', $currentSettingBilling->type ?? 1),
            'due_dates' => self::resolveOptionalField($request, 'due_dates', $currentSettingBilling->due_dates ?? 5),
            'terms_and_conditions' => self::resolveOptionalField($request, 'terms_and_conditions', $currentSettingBilling->terms_and_conditions ?? ''),
            'send_reminder' => self::resolveOptionalField($request, 'send_reminder', $currentSettingBilling->send_reminder ?? 1),
            'send_notifications' => self::resolveOptionalField($request, 'send_notifications', $currentSettingBilling->send_notifications ?? 1),
            'invoice_id' => self::resolveOptionalField($request, 'invoice_id', $currentSettingBilling->invoice_id ?? 1),
        ]);

        $settings = self::query()->updateOrCreate([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
        ], [
            'setting_billing_id' => $settingBilling->id,
        ]);

        return $settings;
    }

    public static function agreements($request, $settings) {

        $supplier_id = self::resolveSupplierId($request);

        $currentSettingAgreement = $settings?->agreement;
        $agreementId = self::resolveOptionalField($request, 'agreement_id', $settings->setting_agreement_id ?? null);

        $settingAgreement = SettingAgreement::query()->updateOrCreate([
            'id' => $agreementId,
        ], [
            'type' => self::resolveOptionalField($request, 'type', $currentSettingAgreement->type ?? 1),
            'terms_and_conditions_purchase' => self::resolveOptionalField($request, 'terms_and_conditions_purchase', $currentSettingAgreement->terms_and_conditions_purchase ?? ''),
            'terms_and_conditions_sales' => self::resolveOptionalField($request, 'terms_and_conditions_sales', $currentSettingAgreement->terms_and_conditions_sales ?? ''),
            'terms_and_conditions_mediation' => self::resolveOptionalField($request, 'terms_and_conditions_mediation', $currentSettingAgreement->terms_and_conditions_mediation ?? ''),
            'terms_and_conditions_business' => self::resolveOptionalField($request, 'terms_and_conditions_business', $currentSettingAgreement->terms_and_conditions_business ?? ''),
            'due_dates' => self::resolveOptionalField($request, 'due_dates', $currentSettingAgreement->due_dates ?? 5),
            'send_reminder' => self::resolveOptionalField($request, 'send_reminder', $currentSettingAgreement->send_reminder ?? 1),
            'send_notifications' => self::resolveOptionalField($request, 'send_notifications', $currentSettingAgreement->send_notifications ?? 1),
        ]);

        $settings = self::query()->updateOrCreate([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
        ], [
            'setting_agreement_id' => $settingAgreement->id,
        ]);

        return $settings;
    }

    public static function notifications($request, $settings) {

        $supplier_id = self::resolveSupplierId($request);

        $currentSettingNotification = $settings?->notification;
        $notificationId = self::resolveOptionalField($request, 'notification_id', $settings->setting_notification_id ?? null);

        $settingNotification = SettingNotification::query()->updateOrCreate([
            'id' => $notificationId,
        ], [
            'notify_via_sound' => self::resolveOptionalField($request, 'notify_via_sound', $currentSettingNotification->notify_via_sound ?? 1),
            'notify_via_email' => self::resolveOptionalField($request, 'notify_via_email', $currentSettingNotification->notify_via_email ?? 0),
            'send_reminders' => self::resolveOptionalField($request, 'send_reminders', $currentSettingNotification->send_reminders ?? 1),
            'notify_on_document_signed' => self::resolveOptionalField($request, 'notify_on_document_signed', $currentSettingNotification->notify_on_document_signed ?? 1),
            'notify_on_agreement_signed' => self::resolveOptionalField($request, 'notify_on_agreement_signed', $currentSettingNotification->notify_on_agreement_signed ?? 1),
            'hours' => self::resolveOptionalField($request, 'hours', $currentSettingNotification->hours ?? 24)
        ]);

        $settings = self::query()->updateOrCreate([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
        ], [
            'setting_notification_id' => $settingNotification->id,
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