<?php
use App\Models\LearningResource;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
Artisan::command('uploads:cleanup {--dry-run}',function(){
 $disk=Storage::disk('public');$referenced=collect()
  ->merge(User::whereNotNull('profile_photo_path')->pluck('profile_photo_path'))
  ->merge(Notice::whereNotNull('attachment_path')->pluck('attachment_path'))
  ->merge(LearningResource::where('resource_url','like','%/storage/%')->pluck('resource_url')->map(fn($url)=>str($url)->after('/storage/')->toString()))
  ->filter()->flip();
 $deleted=0;foreach(['profile-photos','notice-attachments','learning-resources'] as $directory){foreach($disk->allFiles($directory) as $file){if(!$referenced->has($file)){if(!$this->option('dry-run'))$disk->delete($file);$this->line(($this->option('dry-run')?'Would delete: ':'Deleted: ').$file);$deleted++;}}}
 $this->info($deleted.' orphaned upload(s) '.($this->option('dry-run')?'found.':'removed.'));
})->purpose('Remove uploaded files no longer referenced by database records');
Schedule::command('uploads:cleanup')->dailyAt('02:30')->withoutOverlapping();
