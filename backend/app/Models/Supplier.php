<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use App\Events\ForceLogoutUserEvent;
use App\Services\OpenSslService;

use App\Jobs\SendEmailJob;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];
    protected $appends = ['full_name', 'user_name'];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    const PERMISSIONS = [
        'view dashboard',
        'view company',
        'view plan',
        'view sms'
    ];

    /**** Relationship ****/
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function creator() {
        return $this->belongsTo(User::class, 'creator_id', 'id')->withTrashed();
    }

    public function clients() {
        return $this->hasMany(Client::class, 'supplier_id', 'id');
    }

    public function boss() {
        return $this->belongsTo(Supplier::class, 'boss_id', 'id');
    }

    public function billings() {
        return $this->hasMany(Billing::class, 'supplier_id', 'id');
    }

    public function state() {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function settings() {
        return $this->hasOne(Setting::class, 'supplier_id', 'id');
    }

    public function agreements(){
        return $this->hasMany(Agreement::class, 'supplier_id', 'id');
    }

    public function vehicles() {
        return $this->hasMany(Vehicle::class, 'supplier_id', 'id');
    }

    public function documents() {
        return $this->hasMany(Document::class, 'supplier_id', 'id');
    }

    public function payouts() {
        return $this->hasMany(Payout::class, 'supplier_id', 'id');
    }

    public function notes() {
        return $this->hasMany(Note::class, 'supplier_id', 'id');
    }

    public function children() {
        return $this->hasMany(Supplier::class, 'boss_id', 'id');
    }

    public function plan() {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

    /**** Scopes ****/
    public function scopeClientsCount($query)
    {
        return  $query->addSelect(['client_count' => function ($q){
                    $q->selectRaw('COUNT(*)')
                        ->from('suppliers as s')
                        ->join('clients as c', 'c.supplier_id', '=', 's.id')
                        ->whereColumn('s.id', 'suppliers.id');
                }]);
    }

    public function scopeAcceptedSmsCount($query, $date_from = null, $date_to = null)
    {
        $startOfMonth = $date_from ?? Carbon::now()->startOfMonth()->toDateTimeString();
        $endOfToday = $date_to ?? Carbon::now()->endOfDay()->toDateTimeString();

        return $query->addSelect(['sms_accepted_count' => function ($q) use ($startOfMonth, $endOfToday) {
            $q->selectRaw('COUNT(*)')
                ->from('sms_messages')
                ->whereColumn('sms_messages.supplier_id', 'suppliers.id')
                ->where('sms_messages.billable_count', '>', 0)
                ->whereBetween('sms_messages.created_at', [$startOfMonth, $endOfToday]);
        }]);
    }

    public function scopeWhereSearch($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('payout_number', 'LIKE', '%' . $search . '%')
            ->orWhere('sms_sender', 'LIKE', '%' . $search . '%')
            ->orWhereHas('user', function ($uq) use ($search) {
                $uq->withTrashed()
                    ->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', '%' . $search . '%')
                         ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                         ->orWhere('email', 'LIKE', '%' . $search . '%')
                         ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
                });
            })
            ->orWhereHas('user', function ($uq) use ($search) {
                $uq->withTrashed()
                    ->whereHas('userDetail', function ($dq) use ($search) {
                    $dq->where(function ($inner) use ($search) {
                        $inner->where('company', 'LIKE', '%' . $search . '%')
                              ->orWhere('organization_number', 'LIKE', '%' . $search . '%');
                    });
                });
            })
            ->orWhereHas('creator', function ($uq) use ($search) {
                $uq->withTrashed()
                    ->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', '%' . $search . '%')
                         ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                         ->orWhere('email', 'LIKE', '%' . $search . '%')
                         ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
                });
            });
        });
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('id')) {
            $query->where('id', $filters->get('id'));
        }
        
        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('state_id') !== null) {
            $query->where('state_id', $filters->get('state_id'));
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
    public static function createSupplier($request) {
        $user = User::createUser($request);

        if( $request->has('boss_id') )
            $user->assignRole('User');
        else
            $user->assignRole('Supplier');

        $supplier = self::create([
            'user_id' => $user->id,
            'creator_id' => Auth::user()->id,
            'boss_id' => $request->boss_id === 'null' ? null : $request->boss_id,
            'order_id' => $request->order_id === 'null' ? null : $request->order_id,
            'sms_sender' => $request->sms_sender === 'null' ? null : $request->sms_sender,
            'plan_id' => $request->plan_id,
            'is_yearly' => $request->is_yearly,
            'start_date' => $request->start_date  === 'null' ? null : $request->start_date,
            'end_date' => $request->end_date  === 'null' ? null : $request->end_date,
            'sms_price' => $request->sms_price
        ]);

        $user_details = UserDetails::where('user_id', $user->id)->first();
        $user_details->updateOrCreateUser($request, $user);

        return $supplier;
    }

    public static function updateSupplier($request, $supplier) {

        $user = User::with('userDetail')->find($supplier->user_id);

        User::updateUser($request, $user);

        $supplier->update([
            'sms_sender' => $request->sms_sender === 'null' ? null : $request->sms_sender,
            'plan_id' => $request->plan_id,
            'is_yearly' => $request->is_yearly,
            'start_date' => $request->start_date  === 'null' ? null : $request->start_date,
            'end_date' => $request->end_date  === 'null' ? null : $request->end_date,
            'sms_price' => $request->sms_price
        ]);
        
        if( $supplier->boss_id > 0 )
            $user->assignRole('User');
        else
            $user->assignRole('Supplier');

        //update plan to the supplier and all its children
        $ids = self::collectBranchIds([$supplier->id]);

        $suppliers = self::withTrashed()
            ->with(['user' => function ($query) {
                $query->withTrashed();
            }])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($ids as $supplierId) {
            $supplier = $suppliers->get($supplierId);

            if (!$supplier) {
                continue;
            }

            $supplier->plan_id = $request->plan_id;
            $supplier->save();
        }

        return $supplier;
    }

    public static function deleteSupplier($id) {
        self::deleteSuppliers(array($id));
    }

    public static function deleteSuppliers($ids) {
        $ids = self::collectBranchIds($ids);

        DB::transaction(function () use ($ids) {
            $suppliers = self::withTrashed()
                ->with(['user' => function ($query) {
                    $query->withTrashed();
                }])
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            foreach ($ids as $id) {
                $supplier = $suppliers->get($id);

                if (!$supplier) {
                    continue;
                }

                $supplier->state_id = 1;
                $supplier->save();

                if ($supplier->logo) {
                    deleteFile($supplier->logo);
                }

                $supplier->delete();

                if ($supplier->user) {
                    event(new ForceLogoutUserEvent($supplier->user->id));
                    User::deleteUser($supplier->user->id);
                }
            }
        });
    }

    public static function activateSupplier($id) {
        $ids = self::collectBranchIds([$id]);

        $suppliers = self::withTrashed()
            ->with(['user' => function ($query) {
                $query->withTrashed();
            }])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($ids as $supplierId) {
            $supplier = $suppliers->get($supplierId);

            if (!$supplier) {
                continue;
            }

            $supplier->restore();
            $supplier->state_id = 2;
            $supplier->save();

            if ($supplier->user) {
                $supplier->user->restore();
                $supplier->user->assignRole($supplier->boss_id === null ? 'Supplier' : 'User');
            }
        }
    }

    private static function collectBranchIds(array $ids): array {
        $pendingIds = array_values(array_unique(array_filter($ids)));
        $collectedIds = [];

        while (!empty($pendingIds)) {
            $currentId = array_pop($pendingIds);

            if (in_array($currentId, $collectedIds, true)) {
                continue;
            }

            $collectedIds[] = $currentId;

            $childIds = self::withTrashed()
                ->where('boss_id', $currentId)
                ->pluck('id')
                ->all();

            foreach ($childIds as $childId) {
                if (!in_array($childId, $collectedIds, true)) {
                    $pendingIds[] = $childId;
                }
            }
        }

        return $collectedIds;
    }

    public static function updateSwishSettings($request, $id) {
        $supplier = self::with('user')->where('id', $id)->first();

        // Common Name solo con números (sin guiones ni caracteres especiales)
        $payoutNumberClean = preg_replace('/[^0-9]/', '', $request->payout_number);
        $common_name = $payoutNumberClean;

        $supplier->is_payout = $request->is_payout;
        $supplier->payout_number = $request->payout_number;

        //Si se va a guardar el PEM file
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = 'suppliers/pem/';
            
            $filePath = $path . $common_name . '.pem';

            if ($supplier->pem_url && Storage::disk('public')->exists($supplier->pem_url)) {
                Storage::disk('public')->delete($supplier->pem_url);
            }

            Storage::disk('public')->put($filePath, file_get_contents($file));
            $supplier->pem_url = $filePath;
            $supplier->pem_at = now();
        } else { //Se se van a generar CSR y KEY
            $sslService = new OpenSslService();

            //Se crea CSR y KEY
            $csrAndKey = $sslService->generateCsrAndKey(
                $common_name,
                [],
                trim("swish"),
                4096
            );

            //Save CSR file
            $path = 'suppliers/csr/';

            $filePath = $path . $common_name . '.csr';

            if ($supplier->csr_url && Storage::disk('public')->exists($supplier->csr_url)) {
                Storage::disk('public')->delete($supplier->csr_url);
            }

            Storage::disk('public')->put($filePath, $csrAndKey['csr']);
            $supplier->csr_url = $filePath;
            $supplier->csr_at = now();

            //Save KEY file
            $path = 'suppliers/key/';

            $filePath = $path . $common_name . '.key';

            if ($supplier->key_url && Storage::disk('public')->exists($supplier->key_url)) {
                Storage::disk('public')->delete($supplier->key_url);
            }

            Storage::disk('public')->put($filePath, $csrAndKey['private_key']);
            $supplier->key_url = $filePath;
        }

        if ($supplier->isDirty()) {
            $supplier->save();
        } else {
            $supplier->touch();
        }

        return $supplier;
    }

    public static function masterPassword($request, $id) {
        $supplier = self::where('id', $id)->first();
        $supplier->master_password = $request->master_password;
        $supplier->save();

        return $supplier;
    }  

    public static function createUserRelatedToSupplier($request) {
        $user = User::createUser($request);
        $user->assignRole('User');

        $supplier = self::create([
            'user_id' => $user->id,
            'boss_id' => $request->boss_id,
            'company' => $request->company,
            'organization_number' => $request->organization_number,
            'link' => $request->link,
            'address' => $request->address,
            'street' => $request->street,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'bank' => $request->bank,
            'account_number' => $request->account_number,
            'swish' => $request->swish === 'null' ? null : $request->swish
        ]);

        return $supplier;
    }

    public static function updateUserRelatedToSupplier($request, $supplier) {

        $user = User::with('userDetail')->find($supplier->user_id);

        $supplier->update([
            'company' => $request->company,
            'organization_number' => $request->organization_number,
            'link' => $request->link,
            'address' => $request->address,
            'street' => $request->street,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'bank' => $request->bank,
            'account_number' => $request->account_number,
            'swish' => $request->swish === 'null' ? null : $request->swish
        ]);

        User::updateUser($request, $user);
        
        $user->assignRole('User');

        return $supplier;
    }

    public static function cancelSubscription($id) {

        $cancellation_date = now();
        $grace_end_date = now()->addMonths(3); // 3 months grace period

        $supplier = self::where('id', $id)->first();
        $supplier->cancellation_date = $cancellation_date;
        $supplier->grace_end_date = $grace_end_date;
        $supplier->save();


        if (Auth::user()->getRoleNames()[0] === 'Supplier') {//solicito la cancelación el propio proveedor
            //Send mail to Admin
            $company = $supplier->user->userDetail->company ?? ($supplier->user->name . ' ' . $supplier->user->last_name);
            $plan = $supplier->plan->name . ' (' . ($supplier->plan->is_yearly ? 'Årsabonnemang' : 'Månadsabonnemang') . ')';

            $email = env('MAIL_ADMIN', null);
            $subject = 'Uppsägning av prenumeration';
            $text_primary = "har begärt att avsluta sin prenumeration på Bilflogg.<br><br>";
            $text_primary .= "Företag: " . $company . "<br>";
            $text_primary .= "Organisationsnummer: " . $supplier->user->userDetail->organization_number . "<br>";
            $text_primary .= "Nuvarande plan: " . $plan . "<br>";
            $text_primary .= "Uppsägning begärd: " . $cancellation_date->format('Y-m-d') . "<br>";
            $text_primary .= "Uppsägningstid: 3 månader <br>";
            $text_primary .= "Slutdatum: " . $grace_end_date->format('Y-m-d') . "<br>";
            $text_secondary  = "Prenumerationen förblir aktiv under uppsägningstiden och avslutas på angivet slutdatum.<br>";
            $text_secondary .= "Uppsägningen har registrerats i systemet.<br>";

            $data = [
                'user' => $supplier->user->name . ' ' . $supplier->user->last_name ,
                'text_primary' => $text_primary,
                'text_secondary' => $text_secondary,
                'title' => $subject,
                'icon' => asset('/images/important.png')
            ];

            // Send email asynchronously
            SendEmailJob::dispatch(
                'emails.admin.notifications',
                $data,
                $email,
                $subject
            );

            //-------------------------------------------------------------------------
            //Send mail to Supplier

            $email = $supplier->user->email;
            $subject = 'Bekräftelse på uppsägning av din prenumeration';
            $text_primary = "Vi bekräftar att vi har tagit emot och registrerat din uppsägning av prenumerationen hos Bilflogg.<br>";
            $text_primary .= "Enligt avtalet gäller 3 månaders uppsägningstid. Din prenumeration och tillgång till tjänsten fortsätter därför som vanligt under uppsägningstiden.<br><br>";
            $text_primary .= "Plan: " . $plan . "<br>";
            $text_primary .= "Uppsägning registrerad: " . $cancellation_date->format('Y-m-d') . "<br>";
            $text_primary .= "Prenumerationen avslutas: " . $grace_end_date->format('Y-m-d') . "<br>";
            $text_secondary  = "Du har fortsatt tillgång till tjänsten fram till slutdatumet.<br>";
            $text_secondary .= "Har du några frågor kring din uppsägning är du alltid välkommen att kontakta oss.<br>";

            $data = [
                'user' => $supplier->user->name . ' ' . $supplier->user->last_name ,
                'text_primary' => $text_primary,
                'text_secondary' => $text_secondary,
                'title' => $subject,
                'icon' => asset('/images/important.png')
            ];

            // Send email asynchronously
            SendEmailJob::dispatch(
                'emails.suppliers.notifications',
                $data,
                $email,
                $subject
            );
        }

        return $supplier;
    }
    
    public static function activeSubscription($id) {

        $supplier = self::where('id', $id)->first();
   
        //Send mail to Admin
        $company = $supplier->user->userDetail->company ?? ($supplier->user->name . ' ' . $supplier->user->last_name);
        $plan = $supplier->plan->name . ' (' . ($supplier->plan->is_yearly ? 'Årsabonnemang' : 'Månadsabonnemang') . ')';

        $email = env('MAIL_ADMIN', null);
        $subject = 'Begäran om återaktivering';
        $text_primary = "har skickat en begäran om att återaktivera sitt konto och abonnemang hos Bilflogg.<br><br>";
        $text_primary .= "Företag: " . $company . "<br>";
        $text_primary .= "Organisationsnummer: " . $supplier->user->userDetail->organization_number . "<br>";
        $text_primary .= "Tidigare plan: " . $plan . "<br>";
        $text_primary .= "Förfrågan skickad: " . now()->format('Y-m-d') . "<br>";
        $text_secondary  = "Kontakta leverantören för att hantera återaktiveringen och aktivera abonnemanget på nytt.<br>";

        $data = [
            'user' => $supplier->user->name . ' ' . $supplier->user->last_name ,
            'text_primary' => $text_primary,
            'text_secondary' => $text_secondary,
            'title' => $subject,
            'icon' => asset('/images/important.png')
        ];

        // Send email asynchronously
        SendEmailJob::dispatch(
            'emails.admin.notifications',
            $data,
            $email,
            $subject
        );

        return $supplier;
    }

    public static function reactiveSubscription($id) {
        $supplier = self::where('id', $id)->first();
        $supplier->is_subscription_active = 1;
        $supplier->cancellation_date = null;
        $supplier->grace_end_date = null;
        $supplier->save();

        //Send mail to Supplier
        $plan = $supplier->plan->name . ' (' . ($supplier->plan->is_yearly ? 'Årsabonnemang' : 'Månadsabonnemang') . ')';
        $email = $supplier->user->email;
        $subject = 'Bekräftelse på återaktivering av din prenumeration';
        $text_primary = "Vi bekräftar att din prenumeration hos Bilflogg har återaktiverats.<br>";
        $text_primary .= "Din prenumeration och tillgång till tjänsten fortsätter därför som vanligt.<br><br>";
        $text_primary .= "Plan: " . $plan . "<br>";
        $text_secondary  = "Du har fortsatt tillgång till tjänsten.<br>";
        $text_secondary .= "Har du några frågor kring din prenumeration är du alltid välkommen att kontakta oss.<br>";

        $data = [
            'user' => $supplier->user->name . ' ' . $supplier->user->last_name ,
            'text_primary' => $text_primary,
            'text_secondary' => $text_secondary,
            'title' => $subject,
            'icon' => asset('/images/important.png')
        ];

        // Send email asynchronously
        SendEmailJob::dispatch(
            'emails.suppliers.notifications',
            $data,
            $email,
            $subject
        );
        
        //forzar cierre de sesión del usuario
        $ids = self::collectBranchIds([$id]);

        $suppliers = self::withTrashed()
            ->with(['user' => function ($query) {
                $query->withTrashed();
            }])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($ids as $supplierId) {
            $supplier = $suppliers->get($supplierId);

            if (!$supplier) {
                continue;
            }

            if ($supplier->user) {
                event(new ForceLogoutUserEvent($supplier->user->id));
            }
        }

        return $supplier;
    }

    /**** attributes ****/
    public function getFullNameAttribute()
    {
        if ($this->user)
            return "{$this->user->name} {$this->user->last_name} - {$this->user->userDetail->company}";
        else
            return "";
    }

    public function getUserNameAttribute()
    {
        if ($this->user)
            return "{$this->user->name} {$this->user->last_name}";
        else
            return "";
    }

}
