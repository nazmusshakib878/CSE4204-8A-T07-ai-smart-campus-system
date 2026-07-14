<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\CourseSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class StudentDashboardController extends Controller {
 public function show(Request $request):JsonResponse {
  if($request->user()->role!=='student')return response()->json(['status'=>false,'message'=>'Student access is required.'],403);
  $student=$request->user()->studentProfile;if(!$student)return response()->json(['status'=>true,'data'=>$this->emptyData()]);
  $enrollments=$student->enrollments()->with('course.faculty.user:id,name')->orderByDesc('year')->get();
  $courseIds=$enrollments->pluck('course_id')->unique();$attendance=AttendanceRecord::where('student_id',$student->id)->whereIn('course_id',$courseIds)->get();$present=$attendance->whereIn('status',['present','late'])->count();
  $courses=$enrollments->map(fn($e)=>['enrollment_id'=>$e->id,'course_id'=>$e->course_id,'code'=>$e->course?->course_code,'title'=>$e->course?->title,'credits'=>(float)($e->course?->credit_hours??0),'semester'=>$e->semester,'year'=>$e->year,'faculty'=>$e->course?->faculty?->user?->name])->values();
  $results=$student->performanceMetrics()->orderBy('year')->orderBy('semester')->get()->map(fn($m)=>['semester'=>$m->semester,'year'=>$m->year,'semester_gpa'=>$m->semester_gpa,'cgpa'=>$m->cgpa,'completed_credits'=>$m->completed_credits])->values();
  $schedule=CourseSchedule::with('course')->whereIn('course_id',$courseIds)->orderBy('day_of_week')->orderBy('starts_at')->get()->map(fn($s)=>['id'=>$s->id,'code'=>$s->course?->course_code,'title'=>$s->course?->title,'day'=>['SUN','MON','TUE','WED','THU','FRI','SAT'][$s->day_of_week]??'DAY','starts_at'=>substr($s->starts_at,0,5),'ends_at'=>substr($s->ends_at,0,5),'room'=>$s->room,'class_type'=>$s->class_type])->values();
  $byCourse=$courses->map(function($course)use($attendance){$records=$attendance->where('course_id',$course['course_id']);$present=$records->whereIn('status',['present','late'])->count();return ['course_id'=>$course['course_id'],'label'=>$course['code']?:$course['title'],'attendance'=>$records->count()?(int)round($present*100/$records->count()):null];})->values();
  return response()->json(['status'=>true,'data'=>['summary'=>['registered_courses'=>$courseIds->count(),'attendance_percentage'=>$attendance->count()?(int)round($present*100/$attendance->count()):0,'current_cgpa'=>$results->last()['cgpa']??null,'completed_credits'=>$results->last()['completed_credits']??0],'courses'=>$courses,'semester_results'=>$results,'upcoming_classes'=>$schedule,'performance_chart'=>['semesters'=>$results,'courses'=>$byCourse]]]);
 }
 private function emptyData():array{return ['summary'=>['registered_courses'=>0,'attendance_percentage'=>0,'current_cgpa'=>null,'completed_credits'=>0],'courses'=>[],'semester_results'=>[],'upcoming_classes'=>[],'performance_chart'=>['semesters'=>[],'courses'=>[]]];}
}
