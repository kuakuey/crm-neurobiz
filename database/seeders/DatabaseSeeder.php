<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Offering;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@neurobiz.local'],
            [
                'name' => 'Administración Neurobiz',
                'password' => Hash::make('password'),
                'role' => Role::Admin,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'comercial@neurobiz.local'],
            [
                'name' => 'Equipo comercial',
                'password' => Hash::make('password'),
                'role' => Role::Comercial,
                'email_verified_at' => now(),
            ]
        );

        $this->call(CatalogSeeder::class);
        $admin->refresh();
    }
}
