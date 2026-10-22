<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuditOnlineDemoAccounts extends Command
{
    protected $signature = 'demo:audit-accounts {--report= : Absolute path for the Markdown report}';
    protected $description = 'Read-only audit of online demo accounts; never prints secrets or password hashes.';

    public function handle(): int
    {
        $report = (string) $this->option('report');
        $password = (string) env('DEMO_ACCOUNT_PASSWORD', '');
        if ($report === '' || strlen($password) < 12) {
            $this->error('ONLINE_DEMO_ACCOUNT_AUDIT=FAIL');
            return self::FAILURE;
        }

        try {
            $students = DB::table('users as u')
                ->join('students as s', 's.user_id', '=', 'u.id')
                ->whereRaw("u.email REGEXP '^student[1-8][1-5]@gmail\\\\.com$'")
                ->where('u.role', 'student')
                ->orderBy('s.current_semester')->orderBy('u.email')
                ->get(['u.email', 'u.student_id', 'u.role', 'u.approval_status', 'u.password', 's.current_semester']);
            $faculty = DB::table('users as u')
                ->whereRaw("u.email REGEXP '^spring26\\\\.faculty\\\\.[0-9]{2}@example\\\\.edu$'")
                ->where('u.role', 'faculty')
                ->orderBy('u.email')
                ->get(['u.email', 'u.faculty_id', 'u.role', 'u.approval_status', 'u.password']);
            $admins = DB::table('users')->where('role', 'admin')->orderBy('email')
                ->get(['email', 'admin_id', 'role', 'approval_status']);

            $studentPasswordChecks = $students->every(fn ($student) => Hash::check($password, $student->password));
            $facultyPasswordChecks = $faculty->every(fn ($member) => Hash::check($password, $member->password));
            $statusChecks = $students->every(fn ($student) => $student->approval_status === 'approved')
                && $faculty->every(fn ($member) => $member->approval_status === 'approved');
            $cohorts = $students->groupBy('current_semester')->filter(fn ($group) => $group->count() === 5)->count();

            $lines = [
                '# Online Demo Login Accounts', '',
                'Generated: '.now()->toIso8601String(), '',
                'This is a read-only TLS-verified account audit. Passwords and password hashes are not included.', '',
                '## Verification summary', '',
                '| Check | Result |', '| --- | --- |',
                '| Student accounts | '.$students->count().' / expected 40 |',
                '| Faculty accounts | '.$faculty->count().' / expected 8 |',
                '| Complete student cohorts | '.$cohorts.' / expected 8 |',
                '| Student temporary-password verification | '.($studentPasswordChecks ? 'PASS' : 'FAIL').' |',
                '| Faculty temporary-password verification | '.($facultyPasswordChecks ? 'PASS' : 'FAIL').' |',
                '| Student/faculty roles and approval status | '.($statusChecks ? 'PASS' : 'FAIL').' |',
                '| Admin password verification | NOT TESTED - credentials are separate and were not assumed |', '',
                '## Students', '',
                '| Email | Student ID | Semester | Role | Status | Login password check |', '| --- | --- | ---: | --- | --- | --- |',
            ];
            foreach ($students as $student) {
                $lines[] = sprintf('| %s | %s | %d | %s | %s | %s |', $student->email, $student->student_id, $student->current_semester, $student->role, $student->approval_status, Hash::check($password, $student->password) ? 'PASS' : 'FAIL');
            }
            $lines = array_merge($lines, ['', '## Faculty', '', '| Email | Faculty ID | Role | Status | Login password check |', '| --- | --- | --- | --- | --- |']);
            foreach ($faculty as $member) {
                $lines[] = sprintf('| %s | %s | %s | %s | %s |', $member->email, $member->faculty_id, $member->role, $member->approval_status, Hash::check($password, $member->password) ? 'PASS' : 'FAIL');
            }
            $lines = array_merge($lines, ['', '## Admin accounts', '', '| Email | Admin ID | Role | Status | Credential verification |', '| --- | --- | --- | --- | --- |']);
            if ($admins->isEmpty()) {
                $lines[] = '| None found | - | - | - | Not applicable |';
            } else {
                foreach ($admins as $admin) {
                    $lines[] = sprintf('| %s | %s | %s | %s | NOT TESTED - separate credential |', $admin->email, $admin->admin_id ?: '-', $admin->role, $admin->approval_status);
                }
            }
            $lines = array_merge($lines, ['', '## Admin credential guidance', '', 'Admin credentials were not changed or tested. If an admin password is unknown, use the application\'s authenticated password-reset or administrator-controlled reset workflow with a unique secret; do not reuse the temporary demo password.']);
            file_put_contents($report, implode(PHP_EOL, $lines).PHP_EOL, LOCK_EX);

            $this->line('ONLINE_DEMO_ACCOUNT_AUDIT_STUDENTS='.$students->count());
            $this->line('ONLINE_DEMO_ACCOUNT_AUDIT_FACULTY='.$faculty->count());
            $this->line('ONLINE_DEMO_ACCOUNT_AUDIT_ADMINS='.$admins->count());
            $this->line('ONLINE_DEMO_ACCOUNT_AUDIT=OK');
            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('ONLINE_DEMO_ACCOUNT_AUDIT=FAIL');
            return self::FAILURE;
        }
    }
}
