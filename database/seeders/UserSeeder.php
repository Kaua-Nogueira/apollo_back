<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = env('SEED_DEFAULT_PASSWORD', 'password');

        $this->seedUser(
            env('SEED_ADMIN_EMAIL', 'admin@apollo.com.br'),
            env('SEED_ADMIN_NAME', 'Kauã Nogueira'),
            'admin',
            env('SEED_ADMIN_PASSWORD', $defaultPassword),
        );

        $this->seedUser(
            'joao@apollo.com.br',
            'João Santos',
            'technician',
            $defaultPassword,
        );

        $this->seedUser(
            'pedro@apollo.com.br',
            'Pedro Lima',
            'technician',
            $defaultPassword,
        );

        $this->seedUser(
            'funcionario@apollo.com.br',
            'Carlos Silva',
            'employee',
            $defaultPassword,
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

        if ($role !== 'admin') {
            \App\Models\Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => '(98) 98888-7777',
                    'specialty' => $role === 'employee' ? 'Auxiliar Geral' : 'Técnico de Refrigeração',
                    'active' => true,
                ]
            );
        }

        return $user;
    }
}
