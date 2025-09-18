<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            PermissionSeeder::class,
            AdminSeeder::class,

            StateSeeder::class,

            MorePermissionSeeder::class,

            TypeSeeder::class,
            InvoiceSeeder::class,

            IvaSeeder::class,
            CarBodySeeder::class,
            GearBoxSeeder::class,
            BrandSeeder::class,
            ModelSeeder::class,
            FuelSeeder::class,
            EquipmentSeeder::class,
            InventoryPermissionSeeder::class,
            DocumentTypeSeeder::class,  

            AgreementTypeSeeder::class,
            CurrencySeeder::class,
            GuarantyTypeSeeder::class,
            InsuranceTypeSeeder::class,
            PaymentTypeSeeder::class,

            ClientTypeSeeder::class,
            IdentificationSeeder::class,

            CurrencyPermissionSeeder::class,

            AdvanceSeeder::class,

            CommissionTypeSeeder::class
        ]);

    }
}
