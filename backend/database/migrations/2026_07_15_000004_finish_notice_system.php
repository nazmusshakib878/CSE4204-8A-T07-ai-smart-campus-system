<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('notices',function(Blueprint $table){$table->timestamp('expires_at')->nullable()->after('publish_date');$table->timestamp('archived_at')->nullable()->after('expires_at');$table->string('email_delivery_status')->default('not_configured')->after('archived_at');$table->string('sms_delivery_status')->default('not_configured')->after('email_delivery_status');});
  Schema::create('notice_reads',function(Blueprint $table){$table->id();$table->foreignId('notice_id')->constrained('notices')->cascadeOnDelete();$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();$table->timestamp('read_at');$table->unique(['notice_id','user_id']);});
 }
 public function down():void {Schema::dropIfExists('notice_reads');Schema::table('notices',fn(Blueprint $table)=>$table->dropColumn(['expires_at','archived_at','email_delivery_status','sms_delivery_status']));}
};
