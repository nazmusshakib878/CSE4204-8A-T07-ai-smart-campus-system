<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\LearningResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
class LearningResourceController extends Controller {
 public function index(Request $request): JsonResponse { return response()->json(['message'=>'Learning resources retrieved successfully.','data'=>LearningResource::latest()->get()]); }
 public function store(Request $request): JsonResponse { $this->ensureManager($request); $data=$request->validate($this->rules()); if($request->hasFile('resource_file')) $data['resource_url']=Storage::disk('public')->url($request->file('resource_file')->store('learning-resources','public')); unset($data['resource_file']); $data['uploaded_by_user_id']=$request->user()->id; $data['uploaded_by']=$request->user()->name; $resource=LearningResource::create($data); return response()->json(['message'=>'Learning resource created successfully.','data'=>$resource],201); }
 public function show(Request $request,string $id): JsonResponse { return response()->json(['data'=>LearningResource::findOrFail($id)]); }
 public function update(Request $request,string $id): JsonResponse { $resource=LearningResource::findOrFail($id); $this->ensureManager($request,$resource); $data=$request->validate($this->rules()); if($request->hasFile('resource_file')) $data['resource_url']=Storage::disk('public')->url($request->file('resource_file')->store('learning-resources','public')); unset($data['resource_file'],$data['uploaded_by']); $resource->update($data); return response()->json(['message'=>'Learning resource updated successfully.','data'=>$resource]); }
 public function destroy(Request $request,string $id): JsonResponse { $resource=LearningResource::findOrFail($id); $this->ensureManager($request,$resource); $resource->delete(); return response()->json(['message'=>'Learning resource deleted successfully.']); }
 private function ensureManager(Request $request, ?LearningResource $resource = null): void { $user=$request->user(); abort_unless(in_array($user->role,['faculty','admin'],true),403); if($resource && $user->role==='faculty' && (int)$resource->uploaded_by_user_id !== (int)$user->id) abort(403); }
 private function rules(): array { return ['title'=>['required','string','max:255'],'description'=>['nullable','string'],'category'=>['required','string','max:255'],'resource_type'=>['required','string','max:255'],'resource_url'=>['nullable','required_without:resource_file','string','max:2048'],'resource_file'=>['nullable','required_without:resource_url','file','mimes:pdf,doc,docx,ppt,pptx,txt,jpg,jpeg,png','max:10240'],'course_id'=>['nullable','exists:courses,id'],'uploaded_by'=>['nullable','string','max:255']]; }
}
