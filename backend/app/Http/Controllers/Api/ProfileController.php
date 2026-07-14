<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
class ProfileController extends Controller {
 public function update(Request $request): JsonResponse {
  $data=$request->validate(['name'=>['required','string','min:2','max:255'],'phone'=>['nullable','string','max:20','regex:/^\+?[0-9]{10,15}$/'],'department'=>['nullable','string','max:255','exists:departments,name']]);
  $user=$request->user();$user->update($data);
  $user->studentProfile?->update(['department'=>$user->department,'program'=>$user->department]);
  $user->facultyProfile?->update(['department'=>$user->department]);
  return response()->json(['status'=>true,'message'=>'Profile updated successfully.','user'=>$user->fresh()]);
 }
 public function password(Request $request): JsonResponse {
  $data=$request->validate(['current_password'=>['required','current_password'],'password'=>['required','confirmed',Password::min(8)->letters()->mixedCase()->numbers()->symbols()]]);
  $request->user()->update(['password'=>Hash::make($data['password'])]);
  return response()->json(['status'=>true,'message'=>'Password changed successfully.']);
 }
 public function uploadPhoto(Request $request): JsonResponse {
  $request->validate(['photo'=>['required','image','mimes:jpg,jpeg,png,webp','max:2048']]);
  $user=$request->user();if($user->profile_photo_path)Storage::disk('public')->delete($user->profile_photo_path);
  $user->update(['profile_photo_path'=>$request->file('photo')->store('profile-photos','public')]);
  return response()->json(['status'=>true,'message'=>'Profile photo uploaded successfully.','user'=>$user->fresh()]);
 }
 public function deletePhoto(Request $request): JsonResponse {
  $user=$request->user();if($user->profile_photo_path)Storage::disk('public')->delete($user->profile_photo_path);
  $user->update(['profile_photo_path'=>null]);
  return response()->json(['status'=>true,'message'=>'Profile photo removed successfully.','user'=>$user->fresh()]);
 }
}
