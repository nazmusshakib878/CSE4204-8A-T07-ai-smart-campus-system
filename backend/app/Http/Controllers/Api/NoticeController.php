<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class NoticeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Notice::with('author:id,name,role,department')
            ->latest('publish_date')
            ->latest('id');

        if (! $this->isAdmin($request)) {
            $this->applyAudienceFilter($query, $request->user());
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->isStaff($request)) {
            return $this->forbidden();
        }

        $validated = $request->validate($this->rules());
        $validated['target_department'] = $validated['target_department'] ?? null;
        $validated['target_role'] = $validated['target_role'] ?? null;
        $validated['target_semester'] = $validated['target_semester'] ?? null;

        if (! $this->isAdmin($request) && $validated['audience'] !== 'Individual') {
            $validated['audience'] = 'Department';
            $validated['target_department'] = $request->user()->department ?: $validated['target_department'];
            $validated['target_role'] = 'Students';
        }

        $attachment = $request->file('attachment');
        unset($validated['attachment']);

        if ($attachment) {
            $validated['attachment_path'] = $attachment->store('notice-attachments', 'public');
            $validated['attachment_name'] = $attachment->getClientOriginalName();
            $validated['attachment_mime'] = $attachment->getClientMimeType();
            $validated['attachment_size'] = $attachment->getSize();
        }

        $notice = Notice::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'publish_date' => $validated['publish_date'] ?? now(),
        ]);

        return response()->json([
            'message' => 'Notice published successfully.',
            'data' => $notice->load('author:id,name,role,department'),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $notice = Notice::with('author:id,name,role,department')->findOrFail($id);

        if (! $this->isAdmin($request) && ! $this->noticeMatchesUser($notice, $request->user())) {
            return $this->forbidden();
        }

        return response()->json([
            'data' => $notice,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbidden();
        }

        $notice = Notice::findOrFail($id);
        $notice->update($request->validate($this->rules()));

        return response()->json([
            'message' => 'Notice updated successfully.',
            'data' => $notice->load('author:id,name,role,department'),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbidden();
        }

        $notice = Notice::findOrFail($id);
        if ($notice->attachment_path) {
            Storage::disk('public')->delete($notice->attachment_path);
        }
        $notice->delete();

        return response()->json([
            'message' => 'Notice deleted successfully.',
        ]);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'publish_date' => ['nullable', 'date'],
            'category' => ['required', Rule::in(['Academic', 'Academic Risk', 'Exam', 'Holiday', 'Meeting', 'Facility', 'Payment'])],
            'audience' => ['required', Rule::in(['All', 'Students', 'Faculty', 'Department', 'Individual'])],
            'target_department' => ['nullable', 'required_if:audience,Department', 'string', 'max:255'],
            'target_role' => ['nullable', Rule::in(['All', 'Students', 'Faculty'])],
            'target_semester' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['nullable', 'required_if:audience,Individual', 'string', 'max:255'],
            'recipient_reference' => ['nullable', 'required_if:audience,Individual', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    private function applyAudienceFilter(Builder $query, $user): void
    {
        $roleAudience = $user->role === 'faculty' ? 'Faculty' : 'Students';

        $query->where(function (Builder $noticeQuery) use ($user, $roleAudience) {
            $noticeQuery->where('audience', 'All')
                ->orWhere('audience', $roleAudience)
                ->orWhere(function (Builder $departmentQuery) use ($user, $roleAudience) {
                    $departmentQuery->where('audience', 'Department')
                        ->where('target_department', $user->department)
                        ->where(function (Builder $roleQuery) use ($roleAudience) {
                            $roleQuery->whereNull('target_role')
                                ->orWhere('target_role', 'All')
                                ->orWhere('target_role', $roleAudience);
                        })
                        ->where(function (Builder $semesterQuery) use ($user) {
                            $semesterQuery->whereNull('target_semester')
                                ->orWhere('target_semester', '')
                                ->orWhere('target_semester', $user->semester ?? null);
                        });
                })
                ->orWhere(function (Builder $individualQuery) use ($user) {
                    $individualQuery->where('audience', 'Individual')
                        ->where(function (Builder $recipientQuery) use ($user) {
                            $recipientQuery->where('recipient_reference', $user->student_id)
                                ->orWhere('recipient_reference', $user->faculty_id)
                                ->orWhere('recipient_reference', $user->email);
                        });
                });
        });
    }

    private function noticeMatchesUser(Notice $notice, $user): bool
    {
        $query = Notice::whereKey($notice->id);
        $this->applyAudienceFilter($query, $user);

        return $query->exists();
    }

    private function isStaff(Request $request): bool
    {
        return in_array($request->user()->role, ['faculty', 'admin'], true);
    }

    private function isAdmin(Request $request): bool
    {
        return $request->user()->role === 'admin';
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'You do not have permission to manage notices.',
        ], 403);
    }
}