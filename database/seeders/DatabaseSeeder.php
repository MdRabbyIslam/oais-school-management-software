<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First, seed the roles
        $this->call(RoleSeeder::class);

        // Ensure roles exist before creating users
        $superAdminRole = Role::where('name', 'superadmin')->first();

        // Create a SuperAdmin user
        User::factory()->create([
            'name' => 'SuperAdmin',
            'email' => 'admin@oasis.com',
            'password' => Hash::make('1122'),
            'role_id' => $superAdminRole ? $superAdminRole->id : null,
        ]);


        // $this->call(ClassSeeder::class);
        // $this->call(SectionSeeder::class);
        // $this->call(SubjectSeeder::class);
        // $this->call(StudentSeeder::class);
        // $this->call(TeacherSeeder::class);
        // $this->call(AttendanceSeeder::class);
        // $this->call(SubjectAssignmentSeeder::class);

        // Create upcoming academic year if missing and ensure current students have active enrollments
        $this->call(UpcomingAcademicYearSeeder::class);
        $this->call(EnsureActiveEnrollmentsSeeder::class);

        // Exam result system baseline grading setup
        $this->call(GradeSchemeSeeder::class);
        $this->call(GradingPolicySeeder::class);


    }
}
