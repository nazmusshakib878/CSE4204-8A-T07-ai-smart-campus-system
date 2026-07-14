<?php
namespace App\Jobs;
use App\Models\Notice;
use App\Services\NoticeDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class DeliverNoticeNotifications implements ShouldQueue {
 use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
 public int $tries=3;
 public function __construct(public int $noticeId){}
 public function handle(NoticeDeliveryService $delivery):void {if($notice=Notice::find($this->noticeId))$delivery->deliver($notice);}
 public function failed(\Throwable $e):void {$notice=Notice::find($this->noticeId);if(!$notice)return;$updates=[];if($notice->email_delivery_status==='pending')$updates['email_delivery_status']='failed';if($notice->sms_delivery_status==='pending')$updates['sms_delivery_status']='failed';if($updates)$notice->update($updates);}
}