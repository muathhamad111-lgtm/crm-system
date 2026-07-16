<?php

namespace Database\Seeders;

use App\Enums\AppRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $password = Hash::make('password');

        $supportTeamId = DB::table('teams')->value('id');

        $people = [
            ['name' => 'مدير النظام',   'email' => 'admin@altqniah.sa',    'role' => AppRole::SystemAdmin,       'team' => null],
            ['name' => 'موظف الدعم',    'email' => 'staff@altqniah.sa',    'role' => AppRole::SupportStaff,      'team' => $supportTeamId],
            ['name' => 'مشرف الدعم',    'email' => 'sup@altqniah.sa',      'role' => AppRole::SupportSupervisor, 'team' => $supportTeamId],
            ['name' => 'عميل تجريبي',   'email' => 'customer@example.com', 'role' => AppRole::Customer,          'team' => null],
        ];

        foreach ($people as $p) {
            // Single shared uuid: users.id == profiles.id == user_roles.user_id.
            $id = DB::table('users')->where('email', $p['email'])->value('id')
                ?? DB::table('profiles')->where('email', $p['email'])->value('id')
                ?? (string) Str::uuid();

            DB::table('users')->updateOrInsert(
                ['email' => $p['email']],
                [
                    'id' => $id,
                    'name' => $p['name'],
                    'password' => $password,
                    'email_verified_at' => $now,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            DB::table('profiles')->updateOrInsert(
                ['email' => $p['email']],
                [
                    'id' => $id,
                    'full_name' => $p['name'],
                    'account_status' => 'active',
                    'team_id' => $p['team'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            DB::table('user_roles')->updateOrInsert(
                ['user_id' => $id, 'role' => $p['role']->value],
                ['id' => (string) Str::uuid(), 'created_at' => $now]
            );
        }
    }
}
