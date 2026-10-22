<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LiveDemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@nubtk.ac'],
            [
                'name' => 'Live Demo Administrator',
                'phone' => '01700000909',
                'password' => Hash::make($this->demoPassword()),
                'role' => 'admin',
                'department' => null,
                'student_id' => null,
                'faculty_id' => null,
                'admin_id' => 'ADM-LIVE-001',
                'approval_status' => 'approved',
            ]
        );
    }

    private function demoPassword(): string
    {
        $password = (string) env('DEMO_ACCOUNT_PASSWORD', '');
        if (strlen($password) < 12) {
            throw new \RuntimeException('DEMO_ACCOUNT_PASSWORD must be a private environment secret of at least 12 characters.');
        }

        return $password;
    }
}
