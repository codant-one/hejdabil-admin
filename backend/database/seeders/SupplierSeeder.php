<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\UserDetails;
use App\Models\User;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->count(20)->create();

        $users = User::orderBy('id', 'desc')
                     ->limit(20)
                     ->get();
        
        foreach($users as $user)
        {
            Supplier::factory(['user_id' => $user->id])->create();
            UserDetails::factory(['user_id' => $user->id])->create();
            $user->assignRole('Supplier');
        }
    }
    
}
