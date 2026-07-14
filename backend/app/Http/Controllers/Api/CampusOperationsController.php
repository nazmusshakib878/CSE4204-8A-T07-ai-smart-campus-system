<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CampusOperationsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentId = $user->studentProfile?->id;
        $facultyId = Faculty::where('user_id', $user->id)->value('id');
        $courseIds = $user->role === 'student'
            ? DB::table('course_enrollments')->where('student_id', $studentId)->pluck('course_id')
            : ($user->role === 'faculty' ? DB::table('courses')->where('faculty_id', $facultyId)->pluck('id') : null);

        $exams = DB::table('exam_routines')->join('courses', 'courses.id', '=', 'exam_routines.course_id')
            ->when($courseIds !== null, fn ($q) => $q->whereIn('course_id', $courseIds))
            ->select('exam_routines.*', 'courses.course_code', 'courses.title as course_title')->orderBy('exam_date')->get();
        $schedules = DB::table('course_schedules')->join('courses', 'courses.id', '=', 'course_schedules.course_id')
            ->when($courseIds !== null, fn ($q) => $q->whereIn('course_id', $courseIds))
            ->select('course_schedules.*', 'courses.course_code', 'courses.title as course_title')->orderBy('day_of_week')->orderBy('starts_at')->get();
        $events = DB::table('academic_events')->when($user->role !== 'admin', fn ($q) => $q->where(fn ($audience) => $audience->where('audience', 'all')->orWhere('audience', $user->role)))->orderBy('starts_on')->get();
        $fees = $studentId ? DB::table('fee_records')->where('student_id', $studentId)->orderByDesc('year')->get()
            : ($user->role === 'admin' ? DB::table('fee_records')->join('students', 'students.id', '=', 'fee_records.student_id')->join('users', 'users.id', '=', 'students.user_id')->select('fee_records.*', 'users.name as student_name', 'students.student_number')->orderByDesc('year')->get() : collect());
        $tickets = DB::table('helpdesk_tickets')->join('users', 'users.id', '=', 'helpdesk_tickets.user_id')->when($user->role !== 'admin', fn ($q) => $q->where('helpdesk_tickets.user_id', $user->id))->select('helpdesk_tickets.*', 'users.name as requester_name', 'users.email as requester_email', DB::raw('COALESCE(users.student_id, users.faculty_id, users.admin_id) as requester_university_id'))->orderByDesc('helpdesk_tickets.id')->get();
        $leaves = DB::table('faculty_leaves')->join('faculty', 'faculty.id', '=', 'faculty_leaves.faculty_id')->join('users', 'users.id', '=', 'faculty.user_id')
            ->when($user->role === 'faculty', fn ($q) => $q->where('faculty_leaves.faculty_id', $facultyId))
            ->when($user->role === 'student', fn ($q) => $q->whereRaw('1=0'))
            ->select('faculty_leaves.*', 'users.name as faculty_name')->orderByDesc('id')->get();
        $reschedules = DB::table('class_reschedules')->join('courses', 'courses.id', '=', 'class_reschedules.course_id')
            ->when($courseIds !== null, fn ($q) => $q->whereIn('class_reschedules.course_id', $courseIds))
            ->select('class_reschedules.*', 'courses.course_code', 'courses.title as course_title')->orderByDesc('new_date')->get();
        $books = DB::table('library_books')->orderBy('title')->get();
        $loans = DB::table('library_loans')->join('library_books', 'library_books.id', '=', 'library_loans.book_id')->join('users', 'users.id', '=', 'library_loans.user_id')
            ->when($user->role !== 'admin', fn ($q) => $q->where('library_loans.user_id', $user->id))
            ->select('library_loans.*', 'library_books.title as book_title', 'users.name as borrower_name')->orderByDesc('id')->get();

        return response()->json(['status' => true, 'data' => compact('exams', 'schedules', 'events', 'fees', 'tickets', 'leaves', 'reschedules', 'books', 'loans')]);
    }

    public function storeSchedule(Request $request): JsonResponse
    {
        $this->admin($request); $data = $this->scheduleData($request); $this->assertScheduleAvailable($data);
        $id = DB::table('course_schedules')->insertGetId([...$data, 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Class routine created successfully.', 'data' => ['id' => $id]], 201);
    }

    public function updateSchedule(Request $request, int $schedule): JsonResponse
    {
        $this->admin($request); abort_unless(DB::table('course_schedules')->where('id', $schedule)->exists(), 404);
        $data = $this->scheduleData($request); $this->assertScheduleAvailable($data, $schedule);
        DB::table('course_schedules')->where('id', $schedule)->update([...$data, 'updated_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Class routine updated successfully.']);
    }

    public function destroySchedule(Request $request, int $schedule): JsonResponse
    {
        $this->admin($request); abort_unless(DB::table('course_schedules')->where('id', $schedule)->delete(), 404);
        return response()->json(['status' => true, 'message' => 'Class routine deleted successfully.']);
    }

    public function storeExam(Request $request): JsonResponse
    {
        $this->admin($request); $data = $this->examData($request); $this->assertExamAvailable($data);
        $id = DB::table('exam_routines')->insertGetId([...$data, 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Exam routine created successfully.', 'data' => ['id' => $id]], 201);
    }

    public function updateExam(Request $request, int $exam): JsonResponse
    {
        $this->admin($request); abort_unless(DB::table('exam_routines')->where('id', $exam)->exists(), 404);
        $data = $this->examData($request); $this->assertExamAvailable($data, $exam);
        DB::table('exam_routines')->where('id', $exam)->update([...$data, 'updated_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Exam routine updated successfully.']);
    }

    public function destroyExam(Request $request, int $exam): JsonResponse
    {
        $this->admin($request); abort_unless(DB::table('exam_routines')->where('id', $exam)->delete(), 404);
        return response()->json(['status' => true, 'message' => 'Exam routine deleted successfully.']);
    }

    public function storeEvent(Request $request): JsonResponse
    {
        $this->admin($request); $data = $this->eventData($request);
        $id = DB::table('academic_events')->insertGetId([...$data, 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Academic event created successfully.', 'data' => ['id' => $id]], 201);
    }

    public function updateEvent(Request $request, int $event): JsonResponse
    {
        $this->admin($request); abort_unless(DB::table('academic_events')->where('id', $event)->exists(), 404);
        DB::table('academic_events')->where('id', $event)->update([...$this->eventData($request), 'updated_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Academic event updated successfully.']);
    }

    public function destroyEvent(Request $request, int $event): JsonResponse
    {
        $this->admin($request); abort_unless(DB::table('academic_events')->where('id', $event)->delete(), 404);
        return response()->json(['status' => true, 'message' => 'Academic event deleted successfully.']);
    }
    public function storeFee(Request $request): JsonResponse
    {
        $this->admin($request);
        $data = $request->validate(['student_id' => ['required', 'exists:students,id'], 'semester' => ['required', Rule::in(['Spring', 'Fall'])], 'year' => ['required', 'integer'], 'amount_due' => ['required', 'numeric', 'min:0'], 'amount_paid' => ['required', 'numeric', 'min:0'], 'due_date' => ['nullable', 'date'], 'reference' => ['nullable', 'string', 'max:255']]);
        $data['status'] = $data['amount_paid'] >= $data['amount_due'] ? 'paid' : ($data['amount_paid'] > 0 ? 'partial' : 'unpaid');
        DB::table('fee_records')->updateOrInsert(array_intersect_key($data, array_flip(['student_id', 'semester', 'year'])), [...$data, 'updated_by' => $request->user()->id, 'updated_at' => now(), 'created_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Fee status saved.']);
    }

    public function storeTicket(Request $request): JsonResponse
    {
        $data = $request->validate(['category' => ['required', 'string', 'max:50'], 'subject' => ['required', 'string', 'max:255'], 'description' => ['required', 'string', 'max:3000'], 'priority' => ['required', Rule::in(['low', 'medium', 'high'])]]);
        $id = DB::table('helpdesk_tickets')->insertGetId([...$data, 'user_id' => $request->user()->id, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Support ticket submitted.', 'data' => ['id' => $id]], 201);
    }

    public function updateTicket(Request $request, int $ticket): JsonResponse
    {
        $this->admin($request);
        $data = $request->validate(['status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])], 'response' => ['nullable', 'string', 'max:3000']]);
        abort_unless(DB::table('helpdesk_tickets')->where('id', $ticket)->update([...$data, 'assigned_to' => $request->user()->id, 'updated_at' => now()]), 404);
        return response()->json(['status' => true, 'message' => 'Ticket updated.']);
    }

    public function storeLeave(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'faculty', 403);
        $facultyId = Faculty::where('user_id', $request->user()->id)->value('id');
        $data = $request->validate(['starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after_or_equal:starts_on'], 'reason' => ['required', 'string', 'max:2000']]);
        $id = DB::table('faculty_leaves')->insertGetId([...$data, 'faculty_id' => $facultyId, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Leave application submitted.', 'data' => ['id' => $id]], 201);
    }

    public function reviewLeave(Request $request, int $leave): JsonResponse
    {
        $this->admin($request);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])], 'admin_note' => ['nullable', 'string', 'max:2000']]);
        $record = DB::table('faculty_leaves')->where('id', $leave)->where('status', 'pending')->first();
        abort_unless($record, 404);
        DB::table('faculty_leaves')->where('id', $leave)->update([...$data, 'reviewed_by' => $request->user()->id, 'updated_at' => now()]);
        $affected = 0;
        if ($data['status'] === 'approved') {
            $days = collect(\Carbon\CarbonPeriod::create($record->starts_on, $record->ends_on))->map(fn ($date) => $date->dayOfWeek)->unique();
            $affected = DB::table('course_schedules')->join('courses', 'courses.id', '=', 'course_schedules.course_id')->where('courses.faculty_id', $record->faculty_id)->whereIn('course_schedules.day_of_week', $days)->count();
        }
        return response()->json(['status' => true, 'message' => 'Leave application reviewed.'.($affected ? " Warning: {$affected} recurring class slot(s) may need rescheduling." : ''), 'affected_classes' => $affected]);
    }
    public function storeReschedule(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'faculty', 403);
        $facultyId = Faculty::where('user_id', $request->user()->id)->value('id');
        $data = $request->validate(['course_id' => ['required', Rule::exists('courses', 'id')->where('faculty_id', $facultyId)], 'original_date' => ['required', 'date'], 'new_date' => ['required', 'date'], 'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'], 'room' => ['nullable', 'string', 'max:100'], 'reason' => ['nullable', 'string', 'max:2000']]);
        $id = DB::table('class_reschedules')->insertGetId([...$data, 'faculty_id' => $facultyId, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Reschedule request submitted.', 'data' => ['id' => $id]], 201);
    }

    public function reviewReschedule(Request $request, int $reschedule): JsonResponse
    {
        $this->admin($request);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        abort_unless(DB::table('class_reschedules')->where('id', $reschedule)->where('status', 'pending')->update([...$data, 'reviewed_by' => $request->user()->id, 'updated_at' => now()]), 404);
        return response()->json(['status' => true, 'message' => 'Reschedule request reviewed.']);
    }

    public function storeBook(Request $request): JsonResponse
    {
        $this->admin($request);
        $data = $request->validate(['isbn' => ['nullable', 'string', 'max:30', 'unique:library_books,isbn'], 'title' => ['required', 'string', 'max:255'], 'author' => ['required', 'string', 'max:255'], 'category' => ['nullable', 'string', 'max:100'], 'total_copies' => ['required', 'integer', 'min:1'], 'shelf' => ['nullable', 'string', 'max:50']]);
        $id = DB::table('library_books')->insertGetId([...$data, 'available_copies' => $data['total_copies'], 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Library book added.', 'data' => ['id' => $id]], 201);
    }

    public function borrowBook(Request $request, int $book): JsonResponse
    {
        $userId = $request->user()->role === 'admin' ? $request->validate(['user_id' => ['required', 'exists:users,id']])['user_id'] : $request->user()->id;
        DB::transaction(function () use ($book, $userId, $request) {
            $updated = DB::table('library_books')->where('id', $book)->where('available_copies', '>', 0)->decrement('available_copies');
            abort_unless($updated, 422, 'No copy is currently available.');
            DB::table('library_loans')->insert(['book_id' => $book, 'user_id' => $userId, 'borrowed_on' => today(), 'due_on' => today()->addDays(14), 'status' => 'borrowed', 'issued_by' => $request->user()->role === 'admin' ? $request->user()->id : null, 'created_at' => now(), 'updated_at' => now()]);
        });
        return response()->json(['status' => true, 'message' => 'Book borrowed successfully.']);
    }

    public function returnLoan(Request $request, int $loan): JsonResponse
    {
        $record = DB::table('library_loans')->where('id', $loan)->first(); abort_unless($record, 404);
        abort_unless($request->user()->role === 'admin' || $record->user_id === $request->user()->id, 403);
        if ($record->status !== 'returned') DB::transaction(function () use ($record) { DB::table('library_loans')->where('id', $record->id)->update(['status' => 'returned', 'returned_on' => today(), 'updated_at' => now()]); DB::table('library_books')->where('id', $record->book_id)->increment('available_copies'); });
        return response()->json(['status' => true, 'message' => 'Book returned successfully.']);
    }

    private function scheduleData(Request $request): array
    {
        return $request->validate(['course_id' => ['required', 'exists:courses,id'], 'semester' => ['required', Rule::in(['Spring', 'Fall'])], 'year' => ['required', 'integer', 'min:2020', 'max:2100'], 'section' => ['nullable', 'string', 'max:50'], 'day_of_week' => ['required', 'integer', 'min:0', 'max:6'], 'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'], 'room' => ['required', 'string', 'max:100'], 'class_type' => ['required', Rule::in(['lecture', 'lab'])]]);
    }

    private function examData(Request $request): array
    {
        return $request->validate(['course_id' => ['required', 'exists:courses,id'], 'semester' => ['required', Rule::in(['Spring', 'Fall'])], 'year' => ['required', 'integer', 'min:2020', 'max:2100'], 'section' => ['nullable', 'string', 'max:50'], 'exam_type' => ['required', 'string', 'max:40'], 'exam_date' => ['required', 'date'], 'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'], 'room' => ['required', 'string', 'max:100']]);
    }

    private function eventData(Request $request): array
    {
        $request->merge(['is_all_day' => $request->has('is_all_day') ? $request->boolean('is_all_day') : true, 'recurrence' => $request->input('recurrence', 'none')]);
        return $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000'], 'starts_on' => ['required', 'date'], 'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'], 'event_type' => ['required', Rule::in(['academic', 'holiday', 'exam', 'registration', 'deadline', 'orientation'])], 'audience' => ['required', Rule::in(['all', 'student', 'faculty', 'admin'])], 'is_all_day' => ['required', 'boolean'], 'recurrence' => ['required', Rule::in(['none', 'weekly', 'monthly', 'yearly'])]]);
    }

    private function assertScheduleAvailable(array $data, ?int $ignore = null): void
    {
        $facultyId = DB::table('courses')->where('id', $data['course_id'])->value('faculty_id');
        $conflict = DB::table('course_schedules')->join('courses', 'courses.id', '=', 'course_schedules.course_id')
            ->where('course_schedules.semester', $data['semester'])->where('course_schedules.year', $data['year'])->where('course_schedules.day_of_week', $data['day_of_week'])
            ->where('course_schedules.starts_at', '<', $data['ends_at'])->where('course_schedules.ends_at', '>', $data['starts_at'])
            ->when($ignore, fn ($q) => $q->where('course_schedules.id', '!=', $ignore))
            ->where(fn ($q) => $q->where('course_schedules.room', $data['room'])->when($facultyId, fn ($same) => $same->orWhere('courses.faculty_id', $facultyId))->when($data['section'] ?? null, fn ($same) => $same->orWhere('course_schedules.section', $data['section'])))->first();
        if ($conflict) throw \Illuminate\Validation\ValidationException::withMessages(['starts_at' => ['This time conflicts with another class for the same room or faculty member.']]);
    }

    private function assertExamAvailable(array $data, ?int $ignore = null): void
    {
        $facultyId = DB::table('courses')->where('id', $data['course_id'])->value('faculty_id');
        $conflict = DB::table('exam_routines')->join('courses', 'courses.id', '=', 'exam_routines.course_id')
            ->where('exam_routines.exam_date', $data['exam_date'])->where('exam_routines.starts_at', '<', $data['ends_at'])->where('exam_routines.ends_at', '>', $data['starts_at'])
            ->when($ignore, fn ($q) => $q->where('exam_routines.id', '!=', $ignore))
            ->where(fn ($q) => $q->where('exam_routines.room', $data['room'])->when($facultyId, fn ($same) => $same->orWhere('courses.faculty_id', $facultyId))->when($data['section'] ?? null, fn ($same) => $same->orWhere('exam_routines.section', $data['section'])))->first();
        if ($conflict) throw \Illuminate\Validation\ValidationException::withMessages(['starts_at' => ['This time conflicts with another exam for the same room or faculty member.']]);
    }
    private function admin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }
}
