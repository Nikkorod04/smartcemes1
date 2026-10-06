<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\User;
use App\Services\EmployeeIdService;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $employeeId = app(EmployeeIdService::class);

        $accounts = [
            [
                'name' => 'Dr. Lowell A. Quisumbing',
                'email' => 'admin@lnu.com',
                'role' => User::ROLE_ADMIN,
                'profile' => null,
            ],
            [
                'name' => 'Jhoanna Ayles',
                'email' => 'secretary@lnu.com',
                'role' => User::ROLE_SECRETARY,
                'profile' => null,
            ],
            [
                'name' => 'Carlo Sumile',
                'email' => 'faculty1@lnu.com',
                'role' => User::ROLE_FACULTY,
                'profile' => [
                    'department' => 'College of Arts and Sciences',
                    'specialization' => 'Information Technology',
                    'position' => 'Instructor II',
                    'phone' => '0917 553 1180',
                    'address' => 'Tacloban City, Leyte',
                ],
            ],
            [
                'name' => 'Bianca Oledan',
                'email' => 'faculty2@lnu.com',
                'role' => User::ROLE_FACULTY,
                'profile' => [
                    'department' => 'College of Education',
                    'specialization' => 'Reading Education',
                    'position' => 'Instructor I',
                    'phone' => '0918 442 7745',
                    'address' => 'Palo, Leyte',
                ],
            ],
            [
                'name' => 'Nikko Villas',
                'email' => 'faculty3@lnu.com',
                'role' => User::ROLE_FACULTY,
                'profile' => [
                    'department' => 'College of Arts and Sciences',
                    'specialization' => 'Environmental Science',
                    'position' => 'Assistant Professor I',
                    'phone' => '0995 118 3402',
                    'address' => 'Tacloban City, Leyte',
                ],
            ],
            [
                'name' => 'Kent Naputo',
                'email' => 'faculty4@lnu.com',
                'role' => User::ROLE_FACULTY,
                'profile' => [
                    'department' => 'College of Management and Entrepreneurship',
                    'specialization' => 'Entrepreneurship',
                    'position' => 'Instructor III',
                    'phone' => '0906 771 2250',
                    'address' => 'Tanauan, Leyte',
                ],
            ],
            /* The Graduate School (owner request 2026-09-25). `department` holds the
               FULL official unit name so the R3a backfill maps it to GRAD
               deterministically — the department map needs a 'graduate school'
               entry or these two would link to no college at all. */
            [
                'name' => 'Dr. Ramon L. Villamor',
                'email' => 'faculty5@lnu.com',
                'role' => User::ROLE_FACULTY,
                'profile' => [
                    'department' => 'Graduate School',
                    'specialization' => 'Research & Extension Management',
                    'position' => 'Professor II',
                    'phone' => '0921 448 7712',
                    'address' => 'Tacloban City, Leyte',
                ],
            ],
            [
                'name' => 'Dr. Cristina P. Manalo',
                'email' => 'faculty6@lnu.com',
                'role' => User::ROLE_FACULTY,
                'profile' => [
                    'department' => 'Graduate School',
                    'specialization' => 'Community Development',
                    'position' => 'Associate Professor I',
                    'phone' => '0919 330 5584',
                    'address' => 'Palo, Leyte',
                ],
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::create([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => 'password',
                'role' => $account['role'],
            ]);

            if ($account['profile'] !== null) {
                Faculty::create([
                    'user_id' => $user->id,
                    'employee_id' => $employeeId->next(),
                    ...$account['profile'],
                ]);
            }
        }
    }
}
