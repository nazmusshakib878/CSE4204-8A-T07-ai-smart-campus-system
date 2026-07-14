<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ProductionReadinessTest extends TestCase {
 use RefreshDatabase;
 public function test_configured_frontend_origin_receives_cors_header():void {
  $this->withHeaders(['Origin'=>'http://localhost:5173','Access-Control-Request-Method'=>'GET'])->options('/api/departments')->assertNoContent()->assertHeader('Access-Control-Allow-Origin','http://localhost:5173');
 }
 public function test_login_endpoint_is_rate_limited():void {
  for($i=0;$i<5;$i++)$this->postJson('/api/login',['email'=>'limited@example.com','password'=>'wrong'])->assertUnauthorized();
  $this->postJson('/api/login',['email'=>'limited@example.com','password'=>'wrong'])->assertStatus(429);
 }
}
