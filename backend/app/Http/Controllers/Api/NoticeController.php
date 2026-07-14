<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Jobs\DeliverNoticeNotifications;
use App\Models\Notice;
use App\Models\NoticeRead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
class NoticeController extends Controller {
 public function index(Request $request):JsonResponse {
  $query=Notice::with('author:id,name,role,department')->withExists(['reads as is_read'=>fn($q)=>$q->where('user_id',$request->user()->id)])->latest('publish_date')->latest('id');
  if(!$this->isAdmin($request)){$this->applyAudienceFilter($query,$request->user());$query->whereNull('archived_at')->where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()));}
  elseif(!$request->boolean('archived'))$query->whereNull('archived_at');
  $unread=(clone $query)->whereDoesntHave('reads',fn($q)=>$q->where('user_id',$request->user()->id))->count();
  $paginator=$query->paginate(min(max((int)$request->input('per_page',15),1),50));
  return response()->json(['data'=>$paginator->items(),'meta'=>['current_page'=>$paginator->currentPage(),'last_page'=>$paginator->lastPage(),'per_page'=>$paginator->perPage(),'total'=>$paginator->total(),'unread'=>$unread]]);
 }
 public function store(Request $request):JsonResponse {
  if(!$this->isStaff($request))return $this->forbidden();$data=$request->validate($this->rules());$this->scopeFacultyNotice($request,$data);$this->storeAttachment($request,$data);
  $emailStatus=config('notice_delivery.email_enabled')?'pending':'disabled';
  $smsStatus=config('notice_delivery.sms_enabled')?'pending':'disabled';
  $notice=Notice::create([...$data,'user_id'=>$request->user()->id,'publish_date'=>$data['publish_date']??now(),'email_delivery_status'=>$emailStatus,'sms_delivery_status'=>$smsStatus]);
  if($emailStatus==='pending'||$smsStatus==='pending')DeliverNoticeNotifications::dispatch($notice->id);
  return response()->json(['message'=>'Notice published successfully.','data'=>$notice->load('author:id,name,role,department')],201);
 }
 public function show(Request $request,string $id):JsonResponse {$notice=Notice::with('author:id,name,role,department')->findOrFail($id);if(!$this->canAccess($request,$notice))return $this->forbidden();return response()->json(['data'=>$notice]);}
 public function update(Request $request,string $id):JsonResponse {
  $notice=Notice::findOrFail($id);if(!$this->canManage($request,$notice))return $this->forbidden();$data=$request->validate($this->rules());$this->scopeFacultyNotice($request,$data);$this->storeAttachment($request,$data,$notice);$notice->update($data);
  return response()->json(['message'=>'Notice updated successfully.','data'=>$notice->fresh('author:id,name,role,department')]);
 }
 public function destroy(Request $request,string $id):JsonResponse {$notice=Notice::findOrFail($id);if(!$this->canManage($request,$notice))return $this->forbidden();if($notice->attachment_path)Storage::disk('public')->delete($notice->attachment_path);$notice->delete();return response()->json(['message'=>'Notice deleted successfully.']);}
 public function markRead(Request $request,Notice $notice):JsonResponse {if(!$this->canAccess($request,$notice))return $this->forbidden();NoticeRead::updateOrCreate(['notice_id'=>$notice->id,'user_id'=>$request->user()->id],['read_at'=>now()]);return response()->json(['message'=>'Notice marked as read.']);}
 public function archive(Request $request,Notice $notice):JsonResponse {if(!$this->canManage($request,$notice))return $this->forbidden();$notice->update(['archived_at'=>$notice->archived_at?null:now()]);return response()->json(['message'=>$notice->archived_at?'Notice archived.':'Notice restored.','data'=>$notice]);}
 public function download(Request $request,Notice $notice){if(!$this->canAccess($request,$notice)||!$notice->attachment_path||!Storage::disk('public')->exists($notice->attachment_path))abort(404);return Storage::disk('public')->download($notice->attachment_path,$notice->attachment_name?:'notice-attachment.pdf',['Content-Type'=>$notice->attachment_mime?:'application/pdf']);}
 private function storeAttachment(Request $request,array &$data,?Notice $notice=null):void {$file=$request->file('attachment');unset($data['attachment']);if(!$file)return;if($notice?->attachment_path)Storage::disk('public')->delete($notice->attachment_path);$data['attachment_path']=$file->store('notice-attachments','public');$data['attachment_name']=$file->getClientOriginalName();$data['attachment_mime']=$file->getClientMimeType();$data['attachment_size']=$file->getSize();}
 private function scopeFacultyNotice(Request $request,array &$data):void {$data['target_department']=$data['target_department']??null;$data['target_role']=$data['target_role']??null;$data['target_semester']=$data['target_semester']??null;if(!$this->isAdmin($request)&&($data['audience']??'')!=='Individual'){$data['audience']='Department';$data['target_department']=$request->user()->department?:$data['target_department'];$data['target_role']='Students';}}
 private function rules():array{return ['title'=>['required','string','max:255'],'description'=>['required','string','max:5000'],'publish_date'=>['nullable','date'],'expires_at'=>['nullable','date','after:publish_date'],'category'=>['required',Rule::in(['Academic','Academic Risk','Exam','Holiday','Meeting','Facility','Payment'])],'audience'=>['required',Rule::in(['All','Students','Faculty','Department','Individual'])],'target_department'=>['nullable','required_if:audience,Department','string','max:255'],'target_role'=>['nullable',Rule::in(['All','Students','Faculty'])],'target_semester'=>['nullable','string','max:50'],'recipient_name'=>['nullable','required_if:audience,Individual','string','max:255'],'recipient_reference'=>['nullable','required_if:audience,Individual','string','max:255'],'attachment'=>['nullable','file','mimes:pdf','max:10240']];}
 private function applyAudienceFilter(Builder $query,$user):void {$role=$user->role==='faculty'?'Faculty':'Students';$query->where(function($q)use($user,$role){$q->where('audience','All')->orWhere('audience',$role)->orWhere(fn($d)=>$d->where('audience','Department')->where('target_department',$user->department)->where(fn($r)=>$r->whereNull('target_role')->orWhere('target_role','All')->orWhere('target_role',$role)))->orWhere(fn($i)=>$i->where('audience','Individual')->where(fn($r)=>$r->where('recipient_reference',$user->student_id)->orWhere('recipient_reference',$user->faculty_id)->orWhere('recipient_reference',$user->email)));});}
 private function canAccess(Request $request,Notice $notice):bool {if($this->isAdmin($request)||$notice->user_id===$request->user()->id)return true;$q=Notice::whereKey($notice->id);$this->applyAudienceFilter($q,$request->user());return $q->whereNull('archived_at')->where(fn($x)=>$x->whereNull('expires_at')->orWhere('expires_at','>',now()))->exists();}
 private function canManage(Request $request,Notice $notice):bool{return $this->isAdmin($request)||($request->user()->role==='faculty'&&$notice->user_id===$request->user()->id);}
 private function isStaff(Request $request):bool{return in_array($request->user()->role,['faculty','admin'],true);}
 private function isAdmin(Request $request):bool{return $request->user()->role==='admin';}
 private function forbidden():JsonResponse{return response()->json(['status'=>false,'message'=>'You do not have permission to manage this notice.'],403);}
}
