<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Setting;

use App\Jobs\SendEmailJob;

class SettingController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {

            $settings = Setting::with([
                'user',
                'supplier',
                'color',
                'billing',
                'agreement',
                'notification',
                'document'
            ])->where('user_id', $id)->first();

            return response()->json([
                'success' => true,
                'data' => [ 
                    'settings' => $settings ?? null
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }

    public function colors(Request $request, $id): JsonResponse
    {
        try {

            $settings = Setting::where('supplier_id', $id)->first();
            $settings = Setting::colors($request, $settings);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'settings' => $settings
                ]
            ], 200);

        } catch(\Throwable $ex) {
            return response()->json([
                'success' => false,
                'message' => 'server_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function billings(Request $request, $id): JsonResponse
    {
        try {

            $settings = Setting::where('supplier_id', $id)->first();
            $currentSettingBilling = $settings?->billing;
            $currentNotification = $currentSettingBilling?->send_notifications ?? 0;
            $newNotification = $request->input('send_notifications', 0);

            $settings = Setting::billings($request, $settings);

            $clientEmail = env('SUPPORT_SUPPLIER_EMAIL', null);
            $data = [];
            $subject = "";

            if ($currentNotification == 0 && $newNotification != 0 && $clientEmail != null) {
                $settings = Setting::with('supplier.user')->find($settings->id);
                $name = $settings->supplier->user->name . ' ' . $settings->supplier->user->last_name;
               
                $subject = "Leverantören $name har aktiverat sms-tjänsten";

                $data = [
                    'name' => $name,
                    'text' => "SMS-tjänsten har aktiverats för att skicka fakturor från leverantören $name.",
                    'title' => 'Leverantören har aktiverat sms-tjänsten',
                    'icon' => asset('/images/important.png')
                ];

                // Send email asynchronously
                SendEmailJob::dispatch(
                    'emails.admin.notifications',
                    $data,
                    $clientEmail,
                    $subject
                );
            }

            return response()->json([
                'success' => true,
                'data' => [ 
                    'settings' => $settings,
                    'dataEmail' => $data,
                    'clientEmail' => $clientEmail,
                    "subject" => $subject
                ]
            ], 200);

        } catch(\Throwable $ex) {
            return response()->json([
                'success' => false,
                'message' => 'server_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function agreements(Request $request, $id): JsonResponse
    {
        try {

            $settings = Setting::where('supplier_id', $id)->first();
            $currentSettinAgreement = $settings?->agreement;
            $currentNotification = $currentSettinAgreement?->send_notifications ?? 0;
            $newNotification = $request->input('send_notifications', 0);

            $settings = Setting::agreements($request, $settings);

            $clientEmail = env('SUPPORT_SUPPLIER_EMAIL', null);
            $data = [];
            $subject = "";

            if ($currentNotification == 0 && $newNotification != 0 && $clientEmail != null) {
                $settings = Setting::with('supplier.user')->find($settings->id);
                $name = $settings->supplier->user->name . ' ' . $settings->supplier->user->last_name;
               
                $subject = "Leverantören $name har aktiverat sms-tjänsten";

                $data = [
                    'name' => $name,
                    'text' => "SMS-tjänsten har aktiverats för att skicka avtal från leverantören $name.",
                    'title' => 'Leverantören har aktiverat sms-tjänsten',
                    'icon' => asset('/images/important.png')
                ];

                // Send email asynchronously
                SendEmailJob::dispatch(
                    'emails.admin.notifications',
                    $data,
                    $clientEmail,
                    $subject
                );
            }

            return response()->json([
                'success' => true,
                'data' => [ 
                    'settings' => $settings,
                    'dataEmail' => $data,
                    'clientEmail' => $clientEmail,
                    "subject" => $subject
                ]
            ], 200);

        } catch(\Throwable $ex) {
            return response()->json([
                'success' => false,
                'message' => 'server_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function notifications(Request $request, $id): JsonResponse
    {
        try {

            $settings = Setting::where('supplier_id', $id)->first();
            $settings = Setting::notifications($request, $settings);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'settings' => $settings
                ]
            ], 200);

        } catch(\Throwable $ex) {
            return response()->json([
                'success' => false,
                'message' => 'server_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function documents(Request $request, $id): JsonResponse
    {
        try {

            $settings = Setting::where('supplier_id', $id)->first();
            $currentSettingDocument = $settings?->document;
            $currentNotification = $currentSettingDocument?->send_notifications ?? 0;
            $newNotification = $request->input('send_notifications', 0);

            $settings = Setting::documents($request, $settings);
            $clientEmail = env('SUPPORT_SUPPLIER_EMAIL', null);
            $data = [];
            $subject = "";

            if ($currentNotification == 0 && $newNotification != 0 && $clientEmail != null) {
                $settings = Setting::with('supplier.user')->find($settings->id);
                $name = $settings->supplier->user->name . ' ' . $settings->supplier->user->last_name;
               
                $subject = "Leverantören $name har aktiverat sms-tjänsten";

                $data = [
                    'name' => $name,
                    'text' => "SMS-tjänsten har aktiverats för att skicka dokument från leverantören $name.",
                    'title' => 'Leverantören har aktiverat sms-tjänsten',
                    'icon' => asset('/images/important.png')
                ];

                // Send email asynchronously
                SendEmailJob::dispatch(
                    'emails.admin.notifications',
                    $data,
                    $clientEmail,
                    $subject
                );
            }



            return response()->json([
                'success' => true,
                'data' => [ 
                    'settings' => $settings,
                    'dataEmail' => $data,
                    'clientEmail' => $clientEmail,
                    "subject" => $subject
                ]
            ], 200);

        } catch(
            \Throwable $ex
        ) {
            return response()->json([
                'success' => false,
                'message' => 'server_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }
}
