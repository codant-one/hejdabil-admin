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
use App\Models\UserDetails;
use App\Models\Config;

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

        $billing = Billing::with(['client', 'supplier.user.userDetail', 'state'])->find(1);
        $types = Invoice::all();
        $details = json_decode($billing->detail, true);

        /*if (Auth::user()->getRoleNames()[0] === 'Supplier') {
            $company = UserDetails::with(['user'])->find(Auth::user()->id);
            $company->email = Auth::user()->email;

            $user = UserDetails::with(['user'])->find(3);
            $company = $user->user->userDetail;
            $company->email = $user->user->email;

        } else if (Auth::user()->getRoleNames()[0] === 'User') {

                $user = User::with(['userDetail', 'supplier.boss.user.userDetail'])->find(4);
                $company = $user->supplier->boss->user->userDetail;
                $company->email = $user->supplier->boss->user->email;
        } else { //Admin
            $user = User::with(['userDetail', 'supplier.boss.user.userDetail'])->find(4);
            $company = $user->supplier->boss->user->userDetail;
            $company->email = $user->supplier->boss->user->email;
        }
        */

        $configCompany = Config::getByKey('company') ?? ['value' => '[]'];
        $configLogo    = Config::getByKey('logo')    ?? ['value' => '[]'];
        
        // Extraer el "value" soportando array u object
        $getValue = function ($cfg) {
            if (is_array($cfg)) {
                return $cfg['value'] ?? '[]';
            }
            if (is_object($cfg) && isset($cfg->value)) {
                return $cfg->value;
            }
            return '[]';
        };
        
        $companyRaw = $getValue($configCompany);
        $logoRaw    = $getValue($configLogo);
        
        // Decodificar con tolerancia a JSON "doble"
        $decodeSafe = function ($raw) {
            // Primero intento decodificar
            $decoded = json_decode($raw);
        
            // Si json_decode devuelve una string, entonces había JSON doble: decodifico otra vez
            if (is_string($decoded)) {
                $decoded = json_decode($decoded);
            }
        
            // Si sigue sin ser objeto, forzamos un objeto vacío
            if (!is_object($decoded)) {
                $decoded = (object) [];
            }
        
            return $decoded;
        };
        
        $company = $decodeSafe($companyRaw);
        $logoObj    = $decodeSafe($logoRaw);
        
        // Asignar logo si existe en la config del logo
        $company->logo = $logoObj->logo ?? null;
  
        foreach($details as $row)
            $invoices[] = $row;

        return view('pdfs.invoice', 
            compact(
                'company',
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

        $vehicle = Vehicle::with(['user', 'model.brand', 'state'])->find(1);

        $configCompany = Config::getByKey('company') ?? ['value' => '[]'];
        $configLogo    = Config::getByKey('logo')    ?? ['value' => '[]'];
        
        // Extraer el "value" soportando array u object
        $getValue = function ($cfg) {
            if (is_array($cfg)) {
                return $cfg['value'] ?? '[]';
            }
            if (is_object($cfg) && isset($cfg->value)) {
                return $cfg->value;
            }
            return '[]';
        };
        
        $companyRaw = $getValue($configCompany);
        $logoRaw    = $getValue($configLogo);
        
        // Decodificar con tolerancia a JSON "doble"
        $decodeSafe = function ($raw) {
            // Primero intento decodificar
            $decoded = json_decode($raw);
        
            // Si json_decode devuelve una string, entonces había JSON doble: decodifico otra vez
            if (is_string($decoded)) {
                $decoded = json_decode($decoded);
            }
        
            // Si sigue sin ser objeto, forzamos un objeto vacío
            if (!is_object($decoded)) {
                $decoded = (object) [];
            }
        
            return $decoded;
        };
        
        $company = $decodeSafe($companyRaw);
        $logoObj    = $decodeSafe($logoRaw);
        
        // Asignar logo si existe en la config del logo
        $company->logo = $logoObj->logo ?? null;

        return view('pdfs.vehicle', 
            compact(
                'vehicle',
                'company'
            )
        );
    }

    public function agreement() {

        $agreement = Agreement::with([
            'agreement_type',
            'guaranty_type',
            'insurance_type',
            'currency',
            'iva',
            'offer.user',
            'offer.model.brand',
            'commission.vehicle',
            'commission.commission_type',
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
        ])->find(1);

        $user = User::with(['userDetail','roles'])->find(1);
 
        switch ($agreement->agreement_type_id) {
            case 1:
                $pdf = 'pdfs.sales';
                break;
            case 2:
                $pdf = 'pdfs.purchase';
                break;
            case 3:
                $pdf = 'pdfs.mediation';
                break;
            case 4:
                $pdf = 'pdfs.business';
                break;
        }

        if ($user->roles[0]->name === 'Supplier') {
            $user = UserDetails::with(['user'])->find(Auth::user()->id);
            $company = $user->user->userDetail;
            $company->email = $user->user->email;
            $company->name = $user->user->name;
            $company->last_name = $user->user->last_name;
        } else if ($user->roles[0]->name === 'User') {
            $user = User::with(['userDetail', 'supplier.boss.user.userDetail'])->find(Auth::user()->id);
            $company = $user->supplier->boss->user->userDetail;
            $company->email = $user->supplier->boss->user->email;
            $company->name = $user->supplier->boss->user->name;
            $company->last_name = $user->supplier->boss->user->last_name;
        } else { //Admin
            $configCompany = Config::getByKey('company') ?? ['value' => '[]'];
            $configLogo    = Config::getByKey('logo')    ?? ['value' => '[]'];
            $configSignature   = Config::getByKey('signature')    ?? ['value' => '[]'];
            // Extraer el "value" soportando array u object
            $getValue = function ($cfg) {
                if (is_array($cfg)) 
                    return $cfg['value'] ?? '[]';
                if (is_object($cfg) && isset($cfg->value))
                    return $cfg->value;
                return '[]';
            };
            
            $companyRaw = $getValue($configCompany);
            $logoRaw    = $getValue($configLogo);
            $signatureRaw    = $getValue($configSignature);

            $decodeSafe = function ($raw) {
                $decoded = json_decode($raw);

                if (is_string($decoded))
                    $decoded = json_decode($decoded);
            
                if (!is_object($decoded)) 
                    $decoded = (object) [];
            
                return $decoded;
            };
            
            $company = $decodeSafe($companyRaw);
            $logoObj    = $decodeSafe($logoRaw);
            $signatureObj    = $decodeSafe($signatureRaw);

            $company->logo = $logoObj->logo ?? null;
            $company->img_signature = $signatureObj->img_signature ?? null;
        }

        return view($pdf, 
            compact(
                'agreement',
                'user',
                'company'
            )
        );
    }

}
