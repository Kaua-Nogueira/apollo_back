<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = env('SEED_DEFAULT_PASSWORD', 'password');

        $admin = $this->seedUser(
            env('SEED_ADMIN_EMAIL', 'admin@apollo.com.br'),
            env('SEED_ADMIN_NAME', 'Kauã Nogueira'),
            'admin',
            env('SEED_ADMIN_PASSWORD', $defaultPassword),
        );

        $john = $this->seedUser(
            'joao@apollo.com.br',
            'João Santos',
            'technician',
            $defaultPassword,
        );

        $pedro = $this->seedUser(
            'pedro@apollo.com.br',
            'Pedro Lima',
            'technician',
            $defaultPassword,
        );

        Employee::updateOrCreate(
            ['user_id' => $john->id],
            [
                'phone' => '(98) 99123-4500',
                'specialty' => 'Manutenção e limpeza',
                'hired_at' => '2024-02-05',
                'active' => true,
            ],
        );

        Employee::updateOrCreate(
            ['user_id' => $pedro->id],
            [
                'phone' => '(98) 98842-1120',
                'specialty' => 'Instalação',
                'hired_at' => '2025-01-13',
                'active' => true,
            ],
        );
    }

    private function seedUser(string $email, string $name, string $role, string $password): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->fill([
            'name' => $name,
            'role' => $role,
            'active' => true,
        ]);

        if (! $user->exists) {
            $user->password = $password;
        }

        $user->save();

        return $user;
    }
}
