<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use App\Jobs\SendEmailJob;

use App\Models\SettingBilling;
use App\Models\SettingAgreement;
use App\Models\SettingNotification;
use App\Models\SettingDocument;
use App\Models\SettingColor;    

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

    public function document() {
        return $this->belongsTo(SettingDocument::class, 'setting_document_id', 'id');
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
            'sms_message' => self::resolveOptionalField($request, 'sms_message', $currentSettingBilling->sms_message ?? ''),
            'send_reminder' => self::resolveOptionalField($request, 'send_reminder', $currentSettingBilling->send_reminder ?? 1),
            'send_notifications' => self::resolveOptionalField($request, 'send_notifications', $currentSettingBilling->send_notifications ?? 0),
            'invoice_id' => self::resolveOptionalField($request, 'invoice_id', $currentSettingBilling->invoice_id ?? 1),
        ]);

        $settings = self::query()->updateOrCreate([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
        ], [
            'setting_billing_id' => $settingBilling->id,
        ]);

        self::sendMail($supplier_id, 'billing');

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
            'sms_message' => self::resolveOptionalField($request, 'sms_message', $currentSettingAgreement->sms_message ?? ''),
            'due_dates' => self::resolveOptionalField($request, 'due_dates', $currentSettingAgreement->due_dates ?? 5),
            'send_reminder' => self::resolveOptionalField($request, 'send_reminder', $currentSettingAgreement->send_reminder ?? 1),
            'send_notifications' => self::resolveOptionalField($request, 'send_notifications', $currentSettingAgreement->send_notifications ?? 0),
        ]);

        $settings = self::query()->updateOrCreate([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
        ], [
            'setting_agreement_id' => $settingAgreement->id,
        ]);

        self::sendMail($supplier_id, 'agreement');

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

    public static function documents($request, $settings) {

        $supplier_id = self::resolveSupplierId($request);

        $currentSettingDocument = $settings?->document;
        $documentId = self::resolveOptionalField($request, 'document_id', $settings->setting_document_id ?? null);

        $settingDocument = SettingDocument::query()->updateOrCreate([
            'id' => $documentId,
        ], [
            'sms_message' => self::resolveOptionalField($request, 'sms_message', $currentSettingDocument->sms_message ?? ''),
            'due_dates' => self::resolveOptionalField($request, 'due_dates', $currentSettingDocument->due_dates ?? 5),
            'send_reminder' => self::resolveOptionalField($request, 'send_reminder', $currentSettingDocument->send_reminder ?? 1),
            'send_notifications' => self::resolveOptionalField($request, 'send_notifications', $currentSettingDocument->send_notifications ?? 0),
        ]);

        $settings = self::query()->updateOrCreate([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
        ], [
            'setting_document_id' => $settingDocument->id,
        ]);

        self::sendMail($supplier_id, 'document');

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

    private static function sendMail($supplier_id, $module) {

        $settings = Setting::with(
            'supplier.user.userDetail',
            'billing', 
            'agreement', 
            'document'
        )->where('supplier_id', $supplier_id)->first();

        $email = env('MAIL_ADMIN', null);
        $user = $settings->supplier->user->userDetail->company;

        switch ($module) {
            case 'billing':
                $send_notifications = $settings?->billing?->send_notifications ?? 0;
                $subject = $send_notifications ? 'SMS för fakturor har aktiverats' : 'SMS för fakturor har avaktiverats';
                $title = $send_notifications ? 'Aktiverad SMS' : 'Avaktiverad SMS';
                $text_primary = $send_notifications ? 'har aktiverat SMS-utskick för fakturor i sitt Bilflogg-konto.' : 'har avaktiverat SMS-utskick för fakturor i sitt Bilflogg-konto.';
                $text_secondary = $send_notifications ? 'SMS kommer nu att skickas i samband med fakturor enligt företagets inställningar.' : 'SMS kommer inte längre att skickas i samband med fakturor.';
                break;
            case 'agreement':
                $send_notifications = $settings?->agreement?->send_notifications ?? 0;
                $subject = $send_notifications ? 'SMS för avtal har aktiverats' : 'SMS för avtal har avaktiverats';
                $title = $send_notifications ? 'Aktiverad SMS' : 'Avaktiverad SMS';
                $text_primary = $send_notifications ? 'har aktiverat SMS-utskick för avtal i sitt Bilflogg-konto.' : 'har avaktiverat SMS-utskick för avtal i sitt Bilflogg-konto.';
                $text_secondary = $send_notifications ? 'SMS kommer nu att skickas i samband med avtal enligt företagets inställningar.' : 'SMS kommer inte längre att skickas i samband med avtal.';
                break;
            case 'document':
                $send_notifications = $settings?->document?->send_notifications ?? 0;
                $subject = $send_notifications ? 'SMS för E-signering har aktiverats' : 'SMS för E-signering har avaktiverats';
                $title = $send_notifications ? 'Aktiverad SMS' : 'Avaktiverad SMS';
                $text_primary = $send_notifications ? 'har aktiverat SMS-utskick för E-signering i sitt Bilflogg-konto.' : 'har avaktiverat SMS-utskick för E-signering i sitt Bilflogg-konto.';
                $text_secondary = $send_notifications ? 'SMS kommer nu att skickas i samband med digital signering enligt företagets inställningar.' : 'SMS kommer inte längre att skickas i samband med digital signering.';
                break;
            default:
                return; // Exit if the module is not recognized
        }

        $data = [
            'user' => $user,
            'text_primary' => $text_primary,
            'text_secondary' => $text_secondary,
            'title' => $title,
            'icon' => asset('/images/important.png')
        ];

        // Send email asynchronously
        SendEmailJob::dispatch(
            'emails.admin.notifications',
            $data,
            $email,
            $subject
        );
    }
}