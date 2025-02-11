<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Billing;
use App\Models\Invoice;

class TestingController extends Controller
{
    public function emails() {

        $user = User::find(1);

        $url = env('APP_DOMAIN').'/reset-password?token='.Str::random(60).'&user='.$user->email;

        $info = [
            'subject' => 'Password change request',
            'buttonLink' =>  $url ?? null,
            'email' => 'emails.auth.forgot_pass_confirmation'
        ]; 
        
        $buttonLink = $url;
        $title = 'testing';
        $text =  'We hope this message finds you well. <br> Please be advised that we have generated a new invoice in your name with the following details:';
        $buttonText = 'Download';
        $user = $user->name . ' ' . $user->last_name;
        $invoice= 1;
        $billing = Billing::find(33);
        $text_info = 'Please find attached the invoice in PDF format. You can download and review it at any time. <br> If you have any questions or need more information, please do not hesitate to contact us.';
        $pdfFile = 'pdfFile';

        // $data = [
        //     'title' => $info['title'] ?? null,
        //     'user' => $user->name . ' ' . $user->last_name,
        //     'email' => $user->email,
        //     'password' => Str::random(10),
        //     'text' => $info['text'] ?? null,
        //     'buttonLink' =>  $info['buttonLink'] ?? null,
        //     'buttonText' =>  $info['buttonText'] ?? null
        // ];

        return view('emails.invoices.notifications', 
            compact(
                'invoice',
                'billing',
                'buttonLink',
                'buttonText',
                'title',
                'text',
                'text_info',
                'user',
                'pdfFile'
            )
        );
    }

    public function pdfs() {

        $billing = Billing::with(['client', 'supplier.user', 'state'])->find(33);
        $types = Invoice::all();
        $details = json_decode($billing->detail, true);

        foreach($details as $row)
            $invoices[] = $row;

        return view('pdfs.invoice', 
            compact(
                'billing',
                'types',
                'invoices'
            )
        );
    }

}
