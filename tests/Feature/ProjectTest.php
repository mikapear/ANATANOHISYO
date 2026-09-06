<?php
namespace Tests\Feature;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ProjectTest extends TestCase{
 use RefreshDatabase;
 public function test_guest_cannot_access_projects():void{$this->get('/projects')->assertRedirect(route('login'));}
 public function test_user_can_create_view_and_update_project():void{$user=User::factory()->create();$this->actingAs($user)->get('/projects/create')->assertOk()->assertSee('暮らしの予定を追加する');$response=$this->post('/projects',['name'=>'研究発表','description'=>'秋の発表準備','start_date'=>'2026-08-25','due_date'=>'2026-10-01','status'=>'active']);$project=Project::where('user_id',$user->id)->firstOrFail();$response->assertRedirect(route('projects.show',$project));$this->get(route('projects.show',$project))->assertOk()->assertSee('研究発表')->assertSee('秋の発表準備');$this->put(route('projects.update',$project),['name'=>'研究発表・更新','status'=>'on_hold'])->assertRedirect(route('projects.show',$project));$this->assertDatabaseHas('projects',['id'=>$project->id,'name'=>'研究発表・更新','status'=>'on_hold']);}
 public function test_due_date_cannot_be_before_start_date():void{$user=User::factory()->create();$this->actingAs($user)->post('/projects',['name'=>'日付確認','start_date'=>'2026-09-01','due_date'=>'2026-08-01','status'=>'active'])->assertSessionHasErrors('due_date');}
 public function test_project_list_only_shows_own_projects():void{$user=User::factory()->create();Project::factory()->for($user)->create(['name'=>'本人の計画']);Project::factory()->for(User::factory())->create(['name'=>'他人の計画']);$this->actingAs($user)->get('/projects')->assertOk()->assertSee('本人の計画')->assertDontSee('他人の計画');}
 public function test_user_cannot_view_change_or_delete_another_users_project():void{$user=User::factory()->create();$project=Project::factory()->for(User::factory())->create();$this->actingAs($user)->get(route('projects.show',$project))->assertForbidden();$this->put(route('projects.update',$project),['name'=>'変更','status'=>'active'])->assertForbidden();$this->delete(route('projects.destroy',$project))->assertForbidden();}
 public function test_deleting_project_keeps_related_todo_and_activity_log():void{$user=User::factory()->create();$project=Project::factory()->for($user)->create();$todo=Todo::factory()->for($user)->create(['project_id'=>$project->id]);$log=ActivityLog::factory()->for($user)->create(['project_id'=>$project->id]);$this->actingAs($user)->delete(route('projects.destroy',$project))->assertRedirect(route('projects.index'));$this->assertDatabaseMissing('projects',['id'=>$project->id]);$this->assertDatabaseHas('todos',['id'=>$todo->id,'project_id'=>null]);$this->assertDatabaseHas('activity_logs',['id'=>$log->id,'project_id'=>null]);}
}
