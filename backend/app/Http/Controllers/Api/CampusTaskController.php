<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\CampusTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
class CampusTaskController extends Controller {
 public function index(Request $request): JsonResponse { $q=CampusTask::latest(); if($request->user()->role!=='admin') $q->where('assigned_to_user_id',$request->user()->id); return response()->json(['data'=>$q->get()]); }
 public function store(Request $request): JsonResponse { $data=$request->validate($this->rules()); $data['assigned_to_user_id']=$request->user()->id; $data['assigned_to']=$request->user()->name; $task=CampusTask::create($data); return response()->json(['message'=>'Task created successfully.','data'=>$task],201); }
 public function show(Request $request,string $id): JsonResponse { $task=CampusTask::findOrFail($id); abort_unless(Gate::forUser($request->user())->allows('view',$task),403); return response()->json(['data'=>$task]); }
 public function update(Request $request,string $id): JsonResponse { $task=CampusTask::findOrFail($id); abort_unless(Gate::forUser($request->user())->allows('update',$task),403); $data=$request->validate($this->rules()); unset($data['assigned_to']); $task->update($data); return response()->json(['message'=>'Task updated successfully.','data'=>$task]); }
 public function destroy(Request $request,string $id): JsonResponse { $task=CampusTask::findOrFail($id); abort_unless(Gate::forUser($request->user())->allows('delete',$task),403); $task->delete(); return response()->json(['message'=>'Task deleted successfully.']); }
 private function rules(): array { return ['title'=>['required','string','max:255'],'description'=>['nullable','string'],'assigned_to'=>['nullable','string','max:255'],'due_date'=>['nullable','date'],'status'=>['nullable',Rule::in(['pending','in_progress','completed'])],'priority'=>['nullable',Rule::in(['low','medium','high'])]]; }
}
