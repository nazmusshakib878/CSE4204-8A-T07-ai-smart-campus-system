<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Student;
use Illuminate\Http\Request;

class AcademicExportController extends Controller
{
    public function transcript(Request $request, ?Student $student = null)
    {
        $student = $this->studentFor($request, $student);
        $student->load(['user', 'academicRecords.course', 'performanceMetrics']);
        $rows = $student->academicRecords->sortBy(fn ($r) => sprintf('%04d-%s-%s', $r->year, $r->semester, $r->course?->course_code))
            ->map(fn ($r) => '<tr><td>'.e($r->course?->course_code).'</td><td>'.e($r->course?->title).'</td><td>'.e($r->semester.' '.$r->year).'</td><td>'.e($r->course?->credit_hours).'</td><td>'.e($r->grade).'</td></tr>')->implode('');
        $metric = $student->performanceMetrics->sortByDesc('created_at')->first();
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Official Transcript</title><style>body{font-family:Arial;margin:40px;color:#172033}h1{color:#174ea6;margin-bottom:4px}table{width:100%;border-collapse:collapse;margin-top:24px}th,td{border:1px solid #ccd5e4;padding:10px;text-align:left}th{background:#eef4ff}.summary{margin-top:24px;padding:16px;background:#f4f7fb}.print{float:right;padding:10px 18px}@media print{.print{display:none}}</style></head><body><button class="print" onclick="window.print()">Print / Save PDF</button><h1>Northern University of Business and Technology, Khulna</h1><h2>Official Academic Transcript</h2><p><strong>Student:</strong> '.e($student->user?->name).'<br><strong>ID:</strong> '.e($student->student_number).'<br><strong>Department:</strong> '.e($student->department).'</p><table><thead><tr><th>Code</th><th>Course</th><th>Term</th><th>Credits</th><th>Grade</th></tr></thead><tbody>'.$rows.'</tbody></table><div class="summary"><strong>CGPA:</strong> '.e($metric?->cgpa ?? 'N/A').' &nbsp; <strong>Completed credits:</strong> '.e($metric?->completed_credits ?? 0).'</div><script>window.onload=()=>window.print()</script></body></html>';
        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function attendance(Request $request, ?Student $student = null)
    {
        $student = $this->studentFor($request, $student);
        $student->load(['attendanceRecords.course', 'user']);
        $filename = 'attendance-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $student->student_number).'.csv';
        return response()->streamDownload(function () use ($student) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Student ID', 'Student Name', 'Date', 'Course Code', 'Course Title', 'Status']);
            foreach ($student->attendanceRecords->sortByDesc('attendance_date') as $record) fputcsv($file, [$student->student_number, $student->user?->name, $record->attendance_date, $record->course?->course_code, $record->course?->title, ucfirst($record->status)]);
            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function studentFor(Request $request, ?Student $student): Student
    {
        if ($request->user()->role === 'student') return $request->user()->studentProfile ?? abort(404);
        abort_unless(in_array($request->user()->role, ['faculty', 'admin'], true) && $student, 403);
        if ($request->user()->role === 'faculty') {
            $facultyId = Faculty::where('user_id', $request->user()->id)->value('id');
            abort_unless($student->enrollments()->whereHas('course', fn ($q) => $q->where('faculty_id', $facultyId))->exists(), 403);
        }
        return $student;
    }
}
