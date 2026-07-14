<?php
namespace Database\Seeders;
use App\Models\AcademicRecord;
use App\Models\AttendanceRecord;
use App\Models\CampusTask;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSchedule;
use App\Models\Faculty;
use App\Models\Notice;
use App\Models\PerformanceMetric;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class CampusDemoSeeder extends Seeder {
 public function run():void {
  $password=Hash::make('Demo@12345');
  $admin=User::updateOrCreate(['email'=>'admin@nubtkhulna.ac.bd'],['name'=>'NUBTK Admin','phone'=>'01700000001','password'=>$password,'role'=>'admin','department'=>'Computer Science & Engineering','admin_id'=>'ADM-901','student_id'=>null,'faculty_id'=>null,'approval_status'=>'approved']);
  $facultyUser=User::updateOrCreate(['email'=>'faculty.cse@nubtkhulna.ac.bd'],['name'=>'Dr. Nasrin Begum','phone'=>'01700000002','password'=>$password,'role'=>'faculty','department'=>'Computer Science & Engineering','faculty_id'=>'FAC-CSE-9001','student_id'=>null,'admin_id'=>null,'approval_status'=>'approved']);
  $faculty=Faculty::updateOrCreate(['user_id'=>$facultyUser->id],['department'=>$facultyUser->department,'designation'=>'Associate Professor']);
  $studentData=[
   ['email'=>'student1@nubtkhulna.ac.bd','name'=>'Rafiqul Islam','phone'=>'01700000011','id'=>'CSE2026999001'],
   ['email'=>'student2@nubtkhulna.ac.bd','name'=>'Lubna Akter','phone'=>'01700000012','id'=>'CSE2026999002'],
   ['email'=>'student3@nubtkhulna.ac.bd','name'=>'Nasrin Akter','phone'=>'01700000013','id'=>'CSE2026999003'],
  ];
  $students=collect($studentData)->map(function($data)use($password){$user=User::updateOrCreate(['email'=>$data['email']],['name'=>$data['name'],'phone'=>$data['phone'],'password'=>$password,'role'=>'student','department'=>'Computer Science & Engineering','student_id'=>$data['id'],'faculty_id'=>null,'admin_id'=>null,'approval_status'=>'approved']);return Student::updateOrCreate(['user_id'=>$user->id],['student_number'=>$data['id'],'department'=>$user->department,'program'=>'BSc in CSE','current_semester'=>8]);});
  $courses=collect([
   ['course_code'=>'CSE-4103','title'=>'Artificial Intelligence','credit_hours'=>3,'description'=>'Core AI concepts and applications'],
   ['course_code'=>'CSE-4105','title'=>'Computer Networks','credit_hours'=>3,'description'=>'Network architecture and protocols'],
   ['course_code'=>'CSE-4107','title'=>'Database Systems','credit_hours'=>3,'description'=>'Database design and implementation'],
  ])->map(fn($data)=>Course::updateOrCreate(['course_code'=>$data['course_code']],$data+['faculty_id'=>$faculty->id,'department'=>'Computer Science & Engineering','is_active'=>true]));
  $semesters=['Spring','Spring','Spring'];$year=(int)now()->year;
  foreach($students as $si=>$student){foreach($courses as $ci=>$course){CourseEnrollment::updateOrCreate(['course_id'=>$course->id,'student_id'=>$student->id],['semester'=>$semesters[$ci],'year'=>$year]);$statuses=$si===2?['present','absent','absent','present','absent']:['present','present','late','present','absent'];foreach($statuses as $di=>$status){AttendanceRecord::updateOrCreate(['student_id'=>$student->id,'course_id'=>$course->id,'attendance_date'=>now()->subDays(14-$di)->toDateString()],['status'=>$status]);}AcademicRecord::updateOrCreate(['student_id'=>$student->id,'course_id'=>$course->id,'semester'=>'Spring','year'=>$year],['grade'=>[['A','A-','B+'],['B+','A','B'],['C','D','C+']][$si][$ci]]);}}
  foreach($students as $i=>$student){PerformanceMetric::updateOrCreate(['student_id'=>$student->id,'semester'=>'Spring','year'=>$year],['semester_gpa'=>[3.75,3.55,2.25][$i],'cgpa'=>[3.68,3.49,2.41][$i],'completed_credits'=>[96,90,72][$i]]);}
  $schedule=[[$courses[0],1,'09:00','10:30','Room 202','lecture'],[$courses[1],3,'11:00','12:30','Room 105','lecture'],[$courses[2],5,'14:00','16:00','Lab 201','lab']];
  foreach($schedule as [$course,$day,$start,$end,$room,$type])CourseSchedule::updateOrCreate(['course_id'=>$course->id,'day_of_week'=>$day,'starts_at'=>$start],['ends_at'=>$end,'room'=>$room,'class_type'=>$type]);
  Notice::updateOrCreate(['title'=>'Mid-Semester Examination Schedule'],['user_id'=>$admin->id,'description'=>'The mid-semester examination schedule is now available. Check your department notice board for details.','publish_date'=>now(),'expires_at'=>now()->addDays(30),'category'=>'Exam','audience'=>'Students','target_role'=>'Students','email_delivery_status'=>'not_configured','sms_delivery_status'=>'not_configured']);
  Notice::updateOrCreate(['title'=>'CSE Project Presentation'],['user_id'=>$facultyUser->id,'description'=>'AI Smart Campus project presentation will be held next week.','publish_date'=>now(),'category'=>'Academic','audience'=>'Department','target_department'=>'Computer Science & Engineering','target_role'=>'Students','email_delivery_status'=>'not_configured','sms_delivery_status'=>'not_configured']);
  foreach($students as $i=>$student)CampusTask::updateOrCreate(['title'=>'Prepare for AI Smart Campus presentation','assigned_to_user_id'=>$student->user_id],['description'=>'Review the project workflow and demonstration data.','assigned_to'=>$student->user?->name,'due_date'=>now()->addDays(7)->toDateString(),'status'=>$i===0?'in_progress':'pending','priority'=>'high']);
  $this->command?->info('Campus demo data seeded. Login password for all demo accounts: Demo@12345');
 }
}
