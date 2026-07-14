<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\LearningResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
class LearningResourceController extends Controller {
 public function index(Request $request): JsonResponse { $q=LearningResource::latest(); if($request->user()->role==='student'){ $courseIds=$request->user()->studentProfile?->courses()->pluck('courses.id')??collect(); $q->where(fn($x)=>$x->whereNull('course_id')->orWhereIn('course_id',$courseIds)); } return response()->json(['message'=>'Learning resources retrieved successfully.','data'=>$q->get()]); }
 public function store(Request $request): JsonResponse { abort_unless(Gate::forUser($request->user())->allows('create',LearningResource::class),403); $data=$request->validate($this->rules()); if($request->hasFile('resource_file')) $data['resource_url']=Storage::disk('public')->url($request->file('resource_file')->store('learning-resources','public')); unset($data['resource_file']); $data['uploaded_by_user_id']=$request->user()->id; $data['uploaded_by']=$request->user()->name; $resource=LearningResource::create($data); return response()->json(['message'=>'Learning resource created successfully.','data'=>$resource],201); }
 public function show(Request $request,string $id): JsonResponse { $resource=LearningResource::findOrFail($id); if($request->user()->role==='student' && $resource->course_id && !$request->user()->studentProfile?->courses()->whereKey($resource->course_id)->exists()) abort(403); return response()->json(['data'=>$resource]); }
 public function update(Request $request,string $id): JsonResponse { $resource=LearningResource::findOrFail($id); abort_unless(Gate::forUser($request->user())->allows('update',$resource),403); $data=$request->validate($this->rules()); if($request->hasFile('resource_file')) $data['resource_url']=Storage::disk('public')->url($request->file('resource_file')->store('learning-resources','public')); unset($data['resource_file'],$data['uploaded_by']); $resource->update($data); return response()->json(['message'=>'Learning resource updated successfully.','data'=>$resource]); }
 public function destroy(Request $request,string $id): JsonResponse { $resource=LearningResource::findOrFail($id); abort_unless(Gate::forUser($request->user())->allows('delete',$resource),403); $resource->delete(); return response()->json(['message'=>'Learning resource deleted successfully.']); }
 private function rules(): array { return ['title'=>['required','string','max:255'],'description'=>['nullable','string'],'category'=>['required','string','max:255'],'resource_type'=>['required','string','max:255'],'resource_url'=>['nullable','required_without:resource_file','string','max:2048'],'resource_file'=>['nullable','required_without:resource_url','file','mimes:pdf,doc,docx,ppt,pptx,txt,jpg,jpeg,png','max:10240'],'course_id'=>['nullable','exists:courses,id'],'uploaded_by'=>['nullable','string','max:255']]; }
}
