<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Billing;
use App\Models\Invoice;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\Agreement;

class TestingController extends Controller
{
    public function emails() {

        $user = User::find(1);

        $url = env('APP_DOMAIN').'/reset-password?token='.Str::random(60).'&user='.$user->email;

        $info = [
            'subject' => 'Begäran om ändring av lösenord',
            'buttonLink' =>  $url ?? null,
            'email' => 'emails.auth.forgot_pass_confirmation'
        ]; 
        
        $buttonLink = $url;
        $title = 'testing';
        $text =  'Vi hoppas att detta meddelande får dig att må bra. <br> Vänligen notera att vi har genererat en ny faktura i ditt namn med följande uppgifter:';
        $buttonText = 'Nedladdningar';
        $user = $user->name . ' ' . $user->last_name;
        $invoice= 1;
        $billing = Billing::with(['client', 'supplier.user'])->find(33);
        $text_info = 'Bifogat finns fakturan i PDF-format. Du kan ladda ner och granska den när som helst. <br> Om du har några frågor eller behöver mer information, tveka inte att kontakta oss.';
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

        $billing = Billing::with(['client', 'supplier.user', 'state'])->find(1);
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

    public function reminder() {

        $user = User::find(1);

        $url = env('APP_DOMAIN').'/reset-password?token='.Str::random(60).'&user='.$user->email;

        $info = [
            'subject' => 'Begäran om ändring av lösenord',
            'buttonLink' =>  $url ?? null,
            'email' => 'emails.auth.forgot_pass_confirmation'
        ]; 
        
        $buttonLink = $url;
        $title = 'testing';
        $text =  'Vi hoppas att detta meddelande är till hjälp. <br> Vi skulle vilja informera dig om att följande faktura har förfallit på grund av utebliven betalning inom den fastställda tidsfristen:';
        $buttonText = 'Nedladdningar';
        $user = $user->name . ' ' . $user->last_name;
        $invoice= 1;
        $billing = Billing::with(['client', 'supplier.user'])->find(33);
        $text_info = 'Vi har bifogat en kopia av fakturan i PDF-format för din referens. <br> Vi vill påminna er om att ni kan kontakta oss om ni vill rätta till er situation eller om ni har några frågor om denna faktura. Vi är här för att hjälpa till.';
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

        return view('emails.invoices.reminder', 
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

    public function documents() {

        $document = VehicleDocument::with(['vehicle', 'user'])->find(1);

        $reg_num = $document->vehicle->reg_num;

        return view('emails.documents.vehicles',
            compact(
                'reg_num'
            )
        );
    }

    public function vehicle() {

        $vehicle = Vehicle::with(['user', 'model.brand', 'state', 'iva', 'costs'])->find(1);

        return view('pdfs.vehicle', 
            compact(
                'vehicle'
            )
        );
    }

    public function agreement() {

        $agreement = Agreement::with([
            'agreement_type',
            'guaranty',
            'guaranty_type',
            'insurance_company',
            'insurance_type',
            'currency',
            'iva',
            'payment_types',
            'vehicle_interchange.model.brand',
            'vehicle_interchange.carbody',
            'vehicle_interchange.iva_purchase',
            'agreement_client',
            'vehicle_client.vehicle.model.brand',
            'vehicle_client.vehicle.fuel',
            'vehicle_client.vehicle.gearbox',
            'vehicle_client.vehicle.payment.payment_types',
            'supplier.user'
        ])->find(5);

        $user = User::with(['userDetail'])->find(1);

        switch ($agreement->agreement_type_id) {
            case 1:
                $pdf = 'pdfs.sales';
                break;
            case 2:
                $pdf = 'pdfs.purchase';
                break;
            case 3:
                $pdf = '';
                break;
            case 4:
                $pdf = '';
                break;
        }
        
        return view($pdf, 
            compact(
                'agreement',
                'user'
            )
        );
    }

}
