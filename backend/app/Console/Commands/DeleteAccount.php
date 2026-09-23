<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use Carbon\Carbon;
use App\Events\ForceLogoutUserEvent;
use App\Models\Supplier;
use App\Models\ArchivedContact;
use App\Models\ArchivedContactFile;

use App\Jobs\SendEmailJob;

class DeleteAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suppliers:delete-account {--dry-run : Simulate account deletion without writing changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete accounts for suppliers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        self::deleteAccounts();

        return 0;
    }

    private function deleteAccounts()
    {
        $isDryRun = (bool) $this->option('dry-run');
        $today = Carbon::today();

        $suppliers = 
            Supplier::with('plan', 'user')
                ->where('state_id', 2)
                ->whereNotNull('deletion_requested_at')
                ->whereNotNull('deletion_scheduled_at')
                ->whereDate('deletion_scheduled_at', '=', $today)// es la fecha de gracia
                ->get();

        $processedRootSupplierIds = [];

        foreach($suppliers as $supplier) {
            if (in_array($supplier->id, $processedRootSupplierIds, true)) {
                continue;
            }

            $branchSupplierIds = $this->collectSupplierBranchIds($supplier->id);

            // Archive the supplier's contact information before deletion
            $archivedContact = $this->backupInformation($supplier);

            // We save backup files from the providers before deleting them
            $this->billings($supplier, $archivedContact->id);
            $this->agreements($supplier, $archivedContact->id);
            $this->documents($supplier, $archivedContact->id);
            $this->payouts($supplier, $archivedContact->id);
            $this->invoices($supplier, $archivedContact->id);

            // Backup notification payload before deleting user/supplier records.
            $email = $archivedContact->email ?: optional($supplier->user)->email;
            $fullName = $archivedContact->full_name ?: trim((optional($supplier->user)->name . ' ' . optional($supplier->user)->last_name));

            $subject = 'Ditt Bilflogg-konto har raderats';
            $text_primary = "Ditt Bilflogg-konto har nu avslutats och raderats efter uppsägningstidens slut. Du har inte längre tillgång till kontot eller informationen som tidigare fanns sparad i Bilflogg.<br>";
            $text_primary .= "Tack för tiden du har varit kund hos Bilflogg. Vi uppskattar ditt förtroende och hoppas få möjlighet att välkomna dig tillbaka i framtiden.<br>";
            $text_secondary  = "Om du har några frågor är du alltid välkommen att kontakta oss på info@bilflogg.se.<br><br>";

            $data = [
                'user' => $fullName,
                'text_primary' => $text_primary,
                'text_secondary' => $text_secondary,
                'title' => $subject,
                'icon' => asset('/images/user_deleted.png')
            ];
           
            // Delete supplier root account and all child accounts from DB after archiving.
            $this->purgeSupplierAccountBranch($supplier->id, $isDryRun);
            $processedRootSupplierIds = array_values(array_unique(array_merge($processedRootSupplierIds, $branchSupplierIds)));

            if (!$isDryRun && !empty($email)) {
                // Send email asynchronously
                SendEmailJob::dispatch(
                    'emails.suppliers.notifications',
                    $data,
                    $email,
                    $subject
                );
            }
        }    

        return 0;
    }

    private function collectSupplierBranchIds($rootSupplierId)
    {
        $pendingIds = [$rootSupplierId];
        $visitedIds = [];

        while (!empty($pendingIds)) {
            $currentId = array_pop($pendingIds);

            if (in_array($currentId, $visitedIds, true)) {
                continue;
            }

            $visitedIds[] = $currentId;

            $childIds = Supplier::where('boss_id', $currentId)
                ->pluck('id')
                ->all();

            foreach ($childIds as $childId) {
                if (!in_array($childId, $visitedIds, true)) {
                    $pendingIds[] = $childId;
                }
            }
        }

        return $visitedIds;
    }

    private function purgeSupplierAccountBranch($rootSupplierId, $isDryRun = false)
    {
        $supplierIds = $this->collectSupplierBranchIds($rootSupplierId);

        $userIds = Supplier::withTrashed()
            ->whereIn('id', $supplierIds)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $tablesWithSupplierId = $this->getTablesWithColumn('supplier_id', [
            'suppliers',
            'archived_contacts',
            'archived_contact_files',
        ]);

        $tablesWithUserId = $this->getTablesWithColumn('user_id', [
            'users',
            'archived_contacts',
            'archived_contact_files',
        ]);

        if ($isDryRun) {
            $this->info('DRY RUN - No changes written to database.');
            $this->info('Supplier IDs to purge: ' . implode(',', $supplierIds));
            $this->info('User IDs to purge: ' . implode(',', $userIds));

            foreach ($tablesWithSupplierId as $table) {
                $count = DB::table($table)->whereIn('supplier_id', $supplierIds)->count();
                if ($count > 0) {
                    $this->info("[supplier_id] {$table}: {$count}");
                }
            }

            foreach ($tablesWithUserId as $table) {
                $count = DB::table($table)->whereIn('user_id', $userIds)->count();
                if ($count > 0) {
                    $this->info("[user_id] {$table}: {$count}");
                }
            }

            $supplierCount = Supplier::withTrashed()->whereIn('id', $supplierIds)->count();
            $userCount = DB::table('users')->whereIn('id', $userIds)->count();
            $this->info("[suppliers] suppliers: {$supplierCount}");
            $this->info("[users] users: {$userCount}");

            return;
        }

        DB::transaction(function () use ($supplierIds, $userIds, $tablesWithSupplierId, $tablesWithUserId) {
            foreach ($userIds as $userId) {
                event(new ForceLogoutUserEvent($userId));
            }

            foreach ($tablesWithSupplierId as $table) {
                DB::table($table)->whereIn('supplier_id', $supplierIds)->delete();
            }

            foreach ($tablesWithUserId as $table) {
                DB::table($table)->whereIn('user_id', $userIds)->delete();
            }

            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->whereIn('model_id', $userIds)
                ->delete();

            DB::table('model_has_permissions')
                ->where('model_type', 'App\\Models\\User')
                ->whereIn('model_id', $userIds)
                ->delete();

            DB::table('users')->whereIn('id', $userIds)->delete();

            Supplier::withTrashed()->whereIn('id', $supplierIds)->forceDelete();
        });
    }

    private function getTablesWithColumn($columnName, $excludedTables = [])
    {
        return collect(Schema::getTableListing())
            ->filter(function ($table) use ($columnName, $excludedTables) {
                if (in_array($table, $excludedTables, true)) {
                    return false;
                }

                if (!Schema::hasTable($table)) {
                    return false;
                }

                return Schema::hasColumn($table, $columnName);
            })
            ->values()
            ->all();
    }

    private function backupInformation($supplier)
    {
        $supplier = Supplier::with('user.userDetail')->find($supplier->id);

        return $archivedContact = ArchivedContact::create([
            'original_user_id' => $supplier->user->id,
            'plan_id' => $supplier->plan_id,
            'avatar_id' => $supplier->user->userDetail->avatar_id,
            'full_name' => $supplier->user->name . ' ' . $supplier->user->last_name,
            'email' => $supplier->user->email,
            'avatar' => $supplier->user->avatar,
            'company' => $supplier->user->userDetail->company,
            'organization_number' => $supplier->user->userDetail->organization_number,
            'phone' => $supplier->user->userDetail->phone,
            'landline' => $supplier->user->userDetail->landline,
            'link' => $supplier->user->userDetail->link,
            'bank' => $supplier->user->userDetail->bank,
            'iban' => $supplier->user->userDetail->iban,
            'account_number' => $supplier->user->userDetail->account_number,
            'iban_number' => $supplier->user->userDetail->iban_number,
            'bin' => $supplier->user->userDetail->bin,
            'plus_spin' => $supplier->user->userDetail->plus_spin,
            'swish' => $supplier->user->userDetail->swish,
            'vat' => $supplier->user->userDetail->vat,
            'logo' => $supplier->user->userDetail->logo,
            'img_signature' => $supplier->user->userDetail->img_signature,
            'address' => $supplier->user->userDetail->address,
            'street' => $supplier->user->userDetail->street,
            'postal_code' => $supplier->user->userDetail->postal_code,
            'personal_phone' => $supplier->user->userDetail->personal_phone,
            'personal_landline' => $supplier->user->userDetail->personal_landline,
            'personal_address' => $supplier->user->userDetail->personal_address,
            'payout_number' => $supplier->payout_number,
            'account_created_at' => $supplier->user->created_at,
            'deleted_at' => now(),
            'retention_until' => now()->addYears(7)//hasta cuando se conserva los datos
        ]);   
    }
    
    private function billings($supplier, $archivedContactId)
    {
        $supplier = Supplier::with('billings')->find($supplier->id);
        
        foreach ($supplier->billings as $billing) {
            ArchivedContactFile::create([
                'archived_contact_id' => $archivedContactId,
                'state_id' => $billing->state_id,
                'name' => $this->extractFileName($billing->file),
                'type' => 'billings',
                'file' => $billing->file,
            ]);
        }
    }

    private function agreements($supplier, $archivedContactId)
    {
        $supplier = Supplier::with('agreements.token')->find($supplier->id);
        
        foreach ($supplier->agreements as $agreement) {
            ArchivedContactFile::create([
                'archived_contact_id' => $archivedContactId,
                'signature_status' => $agreement->token->signature_status,
                'name' => $this->extractFileName($agreement->file),
                'type' => 'agreements',
                'file' => $agreement->file,
            ]);
        }
    }

    private function documents($supplier, $archivedContactId)
    {
        $supplier = Supplier::with('documents.token')->find($supplier->id);
        
        foreach ($supplier->documents as $document) {
            ArchivedContactFile::create([
                'archived_contact_id' => $archivedContactId,
                'signature_status' => $document->token->signature_status,
                'name' => $this->extractFileName($document->file),
                'type' => 'documents',
                'file' => $document->file,
            ]);
        }
    }

    private function invoices($supplier, $archivedContactId)
    {
        $supplier = Supplier::with('invoices')->find($supplier->id);
        
        foreach ($supplier->invoices as $invoice) {
            ArchivedContactFile::create([
                'archived_contact_id' => $archivedContactId,
                'state_id' => $invoice->state_id,
                'name' => $this->extractFileName($invoice->file),
                'type' => 'invoices',
                'file' => $invoice->file,
            ]);
        }
    }

    private function payouts($supplier, $archivedContactId)
    {
        $supplier = Supplier::with('payouts')->find($supplier->id);
        
        foreach ($supplier->payouts as $payout) {
            ArchivedContactFile::create([
                'archived_contact_id' => $archivedContactId,
                'payout_state_id' => $payout->payout_state_id,
                'name' => $this->extractFileName($payout->image),
                'type' => 'payouts',
                'file' => $payout->image,
            ]);
        }
    }

    private function extractFileName($filePath)
    {
        if (empty($filePath)) {
            return '';
        }

        return basename(str_replace('\\', '/', $filePath));
    }
}
