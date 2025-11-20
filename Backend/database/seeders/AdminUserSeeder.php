<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Check if admin already exists
        $admin = User::where('email', 'countlessme122@gmail.com')->first();

        if (! $admin) {

            // Create admin account
            $admin = User::create([
                'name'     => 'Admin',
                'email'    => 'countlessme122@gmail.com',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]);

            // Create token for admin
            $token = $admin->createToken('admin_token')->plainTextToken;

            // Display token in console when seeding
            echo "\n==============================\n";
            echo " Admin Account Seeded Successfully \n";
            echo " Email: {$admin->email} \n";
            echo " Password: admin123 \n";
            echo " Role: admin \n";
            echo " API Token: {$token} \n";
            echo "==============================\n\n";

        } else {
            echo "\nAdmin already exists. No new admin created.\n";
        }
    }
}
