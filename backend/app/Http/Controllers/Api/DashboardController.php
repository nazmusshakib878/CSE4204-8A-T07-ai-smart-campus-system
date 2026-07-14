<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\AcademicRecord;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\Notice;
use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller {
 public function admin(Request $request): JsonResponse {
  if($request->user()->role!=='admin') return response()->json(['status'=>false,'message'=>'Admin access is required.'],403);
  $pending=User::where('approval_status','pending')->count();
  $risk=RiskAlert::whereIn('risk_level',['high','critical'])->count();
  $departments=User::query()->where('approval_status','approved')->whereIn('role',['student','faculty'])->whereNotNull('department')->selectRaw('department, SUM(role = ?) as students, SUM(role = ?) as faculty',['student','faculty'])->groupBy('department')->orderByDesc('students')->limit(8)->get()->map(fn($row)=>['label'=>$row->department,'students'=>(int)$row->students,'faculty'=>(int)$row->faculty])->values();
  $activities=collect();
  User::latest()->limit(5)->get()->each(fn($u)=>$activities->push(['type'=>'user','title'=>'User registration','detail'=>$u->name.' · '.ucfirst($u->role),'occurred_at'=>$u->created_at]));
  Notice::latest()->limit(5)->get()->each(fn($n)=>$activities->push(['type'=>'notice','title'=>'Notice published','detail'=>$n->title,'occurred_at'=>$n->created_at]));
  RiskAlert::with('student.user')->latest()->limit(5)->get()->each(fn($r)=>$activities->push(['type'=>'risk','title'=>'Risk alert generated','detail'=>($r->student?->user?->name??'Student').' · '.ucfirst($r->risk_level).' risk','occurred_at'=>$r->created_at]));
  $activities=$activities->sortByDesc('occurred_at')->take(8)->values()->map(fn($a)=>array_merge($a,['occurred_at'=>$a['occurred_at']?->toISOString()]));
  return response()->json(['status'=>true,'data'=>[
   'stats'=>['total_users'=>User::count(),'active_students'=>User::where('role','student')->where('approval_status','approved')->count(),'faculty_members'=>User::where('role','faculty')->where('approval_status','approved')->count(),'pending_actions'=>$pending+$risk],
   'pending_actions'=>['user_approvals'=>$pending,'risk_alerts'=>$risk],
   'departments'=>$departments,'recent_activity'=>$activities,
  ]]);
 }
 public function faculty(Request $request): JsonResponse {
  if($request->user()->role!=='faculty') return response()->json(['status'=>false,'message'=>'Faculty access is required.'],403);
  $faculty=$request->user()->facultyProfile;
  $courseIds=$faculty?->courses()->pluck('id')??collect();
  $studentIds=\DB::table('course_enrollments')->whereIn('course_id',$courseIds)->distinct()->pluck('student_id');
  $attendance=AttendanceRecord::whereIn('course_id',$courseIds)->get();
  $present=$attendance->whereIn('status',['present','late'])->count();
  $avgAttendance=$attendance->count()?(int)round($present*100/$attendance->count()):0;
  $risk=RiskAlert::whereIn('student_id',$studentIds)->whereIn('risk_level',['high','critical'])->count();
  $schedule=CourseSchedule::with('course')->whereIn('course_id',$courseIds)->orderBy('day_of_week')->orderBy('starts_at')->get()->map(fn($s)=>['id'=>$s->id,'code'=>$s->course?->course_code,'title'=>$s->course?->title,'day'=>['SUN','MON','TUE','WED','THU','FRI','SAT'][$s->day_of_week]??'DAY','starts_at'=>substr($s->starts_at,0,5),'ends_at'=>substr($s->ends_at,0,5),'room'=>$s->room,'class_type'=>$s->class_type,'students'=>$s->course?->enrollments()->count()??0]);
  $gradeScores=['A+'=>95,'A'=>90,'A-'=>85,'B+'=>80,'B'=>75,'B-'=>70,'C+'=>65,'C'=>60,'D'=>50,'F'=>0];
  $performance=Course::whereIn('id',$courseIds)->orderBy('course_code')->get()->map(function($course)use($gradeScores){$grades=AcademicRecord::where('course_id',$course->id)->whereNotNull('grade')->pluck('grade')->map(fn($g)=>$gradeScores[strtoupper(trim($g))]??null)->filter(fn($v)=>$v!==null);$records=AttendanceRecord::where('course_id',$course->id)->get();$present=$records->whereIn('status',['present','late'])->count();return ['course_id'=>$course->id,'label'=>trim(($course->course_code??'').' '.($course->title??'')),'average_score'=>$grades->count()?(int)round($grades->avg()):null,'attendance_percentage'=>$records->count()?(int)round($present*100/$records->count()):null];})->values();
  return response()->json(['status'=>true,'data'=>[
   'faculty'=>['name'=>$request->user()->name,'department'=>$faculty?->department?:$request->user()->department,'designation'=>$faculty?->designation],
   'stats'=>['total_students'=>$studentIds->count(),'total_courses'=>$courseIds->count(),'classes_this_week'=>$schedule->count(),'average_attendance'=>$avgAttendance,'at_risk_students'=>$risk],
   'schedule'=>$schedule,'performance'=>$performance,
  ]]);
 }
}
