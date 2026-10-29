<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = env('SEED_ADMIN_PASSWORD');
        if (! is_string($password) || strlen($password) < 12) {
            throw new \RuntimeException('Set SEED_ADMIN_PASSWORD to at least 12 characters before seeding.');
        }

        $user = new User;
        $user->username = $user->generateUsername(nama: 'Super Admin', tglDaftar: date('Y-m-d'));
        $user->name = 'Super Admin';
        $user->email = 'super.admin@gmail.com';
        $user->password = Hash::make(value: $password);
        $user->email_verified_at = Carbon::now();
        $user->is_primary_account = true;
        $user->is_active = true;
        $user->is_super_admin = true;
        $user->save();
    }
}
