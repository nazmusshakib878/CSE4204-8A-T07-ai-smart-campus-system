<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
class RecommendationController extends Controller {
 public function index(Request $request): JsonResponse {
  $user=$request->user();$query=Recommendation::latest();if($user->role!=='admin')$query->where('target_user_id',$user->id);$created=$query->get();
  if($created->isNotEmpty()||$user->role!=='student')return response()->json(['source'=>'advisor','data'=>$created]);
  $student=$user->studentProfile;if(!$student)return response()->json(['source'=>'rule_based','data'=>[]]);
  $enrolled=$student->courses()->pluck('courses.id');$department=$student->department?:$user->department;
  $courses=Course::with('faculty.user:id,name')->where('is_active',true)->whereNotIn('id',$enrolled)->where(function($q)use($department){$q->whereNull('department');if($department)$q->orWhere('department',$department);})->orderBy('course_code')->limit(6)->get();
  $data=$courses->map(function($course)use($user,$department){$same=$department&&strcasecmp((string)$course->department,(string)$department)===0;return ['id'=>'rule-'.$course->id,'course_id'=>$course->id,'title'=>trim(($course->course_code??'Course').' | '.($course->title??'Untitled course')),'description'=>$same?'Recommended because it matches your department and is not already enrolled.':'An active course you have not enrolled in yet.','recommendation_type'=>'Rule-based','target_user'=>$user->name,'score'=>$same?90:75,'source'=>'rule_based','course'=>['course_code'=>$course->course_code,'title'=>$course->title,'credit_hours'=>$course->credit_hours,'faculty'=>$course->faculty?->user?->name]];});
  return response()->json(['source'=>'rule_based','data'=>$data]);
 }
 public function store(Request $request): JsonResponse {abort_unless(Gate::forUser($request->user())->allows('create',Recommendation::class),403);$data=$request->validate($this->rules());$target=User::whereKey($data['target_user_id'])->where('role','student')->firstOrFail();$data['target_user']=$target->name;$data['created_by_user_id']=$request->user()->id;$recommendation=Recommendation::create($data);return response()->json(['message'=>'Recommendation created successfully.','data'=>$recommendation],201);}
 public function show(Request $request,string $id): JsonResponse {$recommendation=Recommendation::findOrFail($id);abort_unless(Gate::forUser($request->user())->allows('view',$recommendation),403);return response()->json(['data'=>$recommendation]);}
 public function update(Request $request,string $id): JsonResponse {$recommendation=Recommendation::findOrFail($id);abort_unless(Gate::forUser($request->user())->allows('update',$recommendation),403);$data=$request->validate($this->rules());$target=User::whereKey($data['target_user_id'])->where('role','student')->firstOrFail();$data['target_user']=$target->name;$recommendation->update($data);return response()->json(['message'=>'Recommendation updated successfully.','data'=>$recommendation]);}
 public function destroy(Request $request,string $id): JsonResponse {$recommendation=Recommendation::findOrFail($id);abort_unless(Gate::forUser($request->user())->allows('delete',$recommendation),403);$recommendation->delete();return response()->json(['message'=>'Recommendation deleted successfully.']);}
 private function rules():array{return ['title'=>['required','string','max:255'],'description'=>['nullable','string'],'recommendation_type'=>['required','string','max:255'],'target_user_id'=>['required','exists:users,id'],'course_id'=>['nullable','exists:courses,id'],'score'=>['nullable','numeric','between:0,100']];}
}
