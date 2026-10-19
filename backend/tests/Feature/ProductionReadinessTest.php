<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
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
 public function test_startup_database_health_reports_only_ok_when_tls_is_verified():void {
  DB::shouldReceive('selectOne')->once()->with('SELECT 1 AS connected')->andReturn((object)['connected'=>1]);
  DB::shouldReceive('selectOne')->once()->with("SHOW STATUS LIKE 'Ssl_cipher'")->andReturn((object)['Value'=>'TLS_AES_256_GCM_SHA384']);
  $this->artisan('database:startup-health')->expectsOutput('DATABASE_TLS_CHECK=OK')->assertExitCode(0);
 }
 public function test_startup_database_health_rejects_missing_critical_schema():void {
  DB::shouldReceive('selectOne')->once()->with('SELECT 1 AS connected')->andReturn((object)['connected'=>1]);
  DB::shouldReceive('selectOne')->once()->with("SHOW STATUS LIKE 'Ssl_cipher'")->andReturn((object)['Value'=>'TLS_AES_256_GCM_SHA384']);
  $schema=Mockery::mock();$schema->shouldReceive('hasTable')->once()->with('users')->andReturn(false);Schema::swap($schema);
  $this->artisan('database:startup-health --critical-schema')->expectsOutput('DATABASE_CRITICAL_SCHEMA=FAIL')->assertExitCode(1);
 }
 public function test_startup_database_health_accepts_complete_critical_schema():void {
  DB::shouldReceive('selectOne')->once()->with('SELECT 1 AS connected')->andReturn((object)['connected'=>1]);
  DB::shouldReceive('selectOne')->once()->with("SHOW STATUS LIKE 'Ssl_cipher'")->andReturn((object)['Value'=>'TLS_AES_256_GCM_SHA384']);
  $schema=Mockery::mock();$schema->shouldReceive('hasTable')->times(6)->andReturn(true);$schema->shouldReceive('hasColumns')->times(6)->andReturn(true);Schema::swap($schema);
  $this->artisan('database:startup-health --critical-schema')->expectsOutput('DATABASE_TLS_CHECK=OK')->expectsOutput('DATABASE_CRITICAL_SCHEMA=OK')->assertExitCode(0);
 }
 public function test_startup_database_health_hides_connection_errors():void {
  DB::shouldReceive('selectOne')->once()->with('SELECT 1 AS connected')->andThrow(new \RuntimeException('secret connection failure'));
  $this->artisan('database:startup-health')->expectsOutput('DATABASE_TLS_CHECK=FAIL')->assertExitCode(1);
 }
}
