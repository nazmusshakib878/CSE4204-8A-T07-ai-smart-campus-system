<?php
namespace App\Services;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
class NoticeDeliveryService {
 public function deliver(Notice $notice):void {
  $recipients=$this->recipients($notice);
  $notice->update([
   'email_delivery_status'=>$this->deliverEmail($notice,$recipients),
   'sms_delivery_status'=>$this->deliverSms($notice,$recipients),
  ]);
 }
 private function recipients(Notice $notice) {
  $query=User::query()->where('approval_status','approved')->whereIn('role',['student','faculty']);
  if($notice->audience==='Students')$query->where('role','student');
  if($notice->audience==='Faculty')$query->where('role','faculty');
  if($notice->audience==='Department'){$query->where('department',$notice->target_department);if($notice->target_role==='Students')$query->where('role','student');if($notice->target_role==='Faculty')$query->where('role','faculty');}
  if($notice->audience==='Individual')$query->where(fn($q)=>$q->where('email',$notice->recipient_reference)->orWhere('student_id',$notice->recipient_reference)->orWhere('faculty_id',$notice->recipient_reference));
  return $query->get(['id','name','email','phone']);
 }
 private function deliverEmail(Notice $notice,$recipients):string {
  if(!config('notice_delivery.email_enabled'))return 'disabled';
  $targets=$recipients->pluck('email')->filter()->unique();if($targets->isEmpty())return 'no_recipients';$sent=0;
  foreach($targets as $email){try{Mail::raw($notice->description,fn($message)=>$message->to($email)->subject($notice->title));$sent++;}catch(\Throwable $e){Log::warning('Notice email delivery failed',['notice_id'=>$notice->id,'error'=>$e->getMessage()]);}}
  return $sent===$targets->count()?'sent':($sent?'partial':'failed');
 }
 private function deliverSms(Notice $notice,$recipients):string {
  if(!config('notice_delivery.sms_enabled')||!config('notice_delivery.sms_url'))return 'disabled';
  $targets=$recipients->pluck('phone')->filter()->unique();if($targets->isEmpty())return 'no_recipients';$sent=0;
  foreach($targets as $phone){try{$request=Http::acceptJson()->timeout(15);if(config('notice_delivery.sms_token'))$request=$request->withToken(config('notice_delivery.sms_token'));$response=$request->post(config('notice_delivery.sms_url'),['to'=>$phone,'message'=>$notice->title."\n".$notice->description,'sender'=>config('notice_delivery.sms_sender')]);if($response->successful())$sent++;else Log::warning('Notice SMS rejected',['notice_id'=>$notice->id,'status'=>$response->status()]);}catch(\Throwable $e){Log::warning('Notice SMS delivery failed',['notice_id'=>$notice->id,'error'=>$e->getMessage()]);}}
  return $sent===$targets->count()?'sent':($sent?'partial':'failed');
 }
}