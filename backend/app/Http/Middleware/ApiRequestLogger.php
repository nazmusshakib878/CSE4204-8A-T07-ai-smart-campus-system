<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
class ApiRequestLogger {
 public function handle(Request $request,Closure $next):Response {
  $started=microtime(true);$response=$next($request);
  Log::channel(config('logging.api_channel','daily'))->info('api_request',['method'=>$request->method(),'path'=>$request->path(),'status'=>$response->getStatusCode(),'duration_ms'=>(int)round((microtime(true)-$started)*1000),'user_id'=>$request->user()?->id,'ip'=>$request->ip(),'request_id'=>$request->header('X-Request-ID')]);
  return $response;
 }
}
