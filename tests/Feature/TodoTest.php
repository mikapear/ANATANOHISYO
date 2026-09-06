<?php
namespace Tests\Feature;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
class TodoTest extends TestCase {
 use RefreshDatabase;
 public function test_guest_cannot_access_todos():void{$this->get('/todos')->assertRedirect(route('login'));}
 public function test_user_can_view_create_and_store_todo():void{$user=User::factory()->create();$project=Project::factory()->for($user)->active()->create();$this->actingAs($user)->get('/todos/create')->assertOk()->assertSee('やることを追加する');$this->post('/todos',['title'=>'通院の準備','project_id'=>$project->id,'due_date'=>'2026-08-26','due_time'=>'09:30','priority'=>'high','recurrence'=>'none','memo'=>'診察券を入れる'])->assertRedirect(route('todos.index'));$this->assertDatabaseHas('todos',['user_id'=>$user->id,'title'=>'通院の準備','project_id'=>$project->id,'priority'=>'high']);$this->get('/todos')->assertOk()->assertSee('通院の準備')->assertSee('診察券を入れる');}
 public function test_user_cannot_assign_another_users_project():void{$user=User::factory()->create();$otherProject=Project::factory()->for(User::factory())->create();$this->actingAs($user)->post('/todos',['title'=>'不正な関連','project_id'=>$otherProject->id,'priority'=>'medium','recurrence'=>'none'])->assertSessionHasErrors('project_id');$this->assertDatabaseMissing('todos',['title'=>'不正な関連']);}
 public function test_user_can_update_and_delete_own_todo():void{$user=User::factory()->create();$todo=Todo::factory()->for($user)->create(['title'=>'変更前']);$this->actingAs($user)->put(route('todos.update',$todo),['title'=>'変更後','priority'=>'low','recurrence'=>'none'])->assertRedirect(route('todos.index'));$this->assertDatabaseHas('todos',['id'=>$todo->id,'title'=>'変更後']);$this->delete(route('todos.destroy',$todo))->assertRedirect(route('todos.index'));$this->assertDatabaseMissing('todos',['id'=>$todo->id]);}
 public function test_user_cannot_change_another_users_todo():void{$user=User::factory()->create();$todo=Todo::factory()->for(User::factory())->create();$this->actingAs($user)->patch(route('todos.complete',$todo))->assertForbidden();$this->delete(route('todos.destroy',$todo))->assertForbidden();}
 public function test_complete_and_reopen_todo():void{$user=User::factory()->create();$todo=Todo::factory()->for($user)->create();$this->actingAs($user)->patch(route('todos.complete',$todo))->assertSessionHas('status','花丸です。今日もひとつ進みましたね。');$this->assertTrue($todo->fresh()->is_completed);$this->patch(route('todos.reopen',$todo))->assertSessionHas('status');$this->assertFalse($todo->fresh()->is_completed);$this->assertNull($todo->fresh()->completed_at);}
 public function test_completing_recurring_todo_creates_only_one_next_todo():void{Carbon::setTestNow('2026-08-25 10:00:00');$user=User::factory()->create();$todo=Todo::factory()->for($user)->create(['title'=>'毎週の確認','due_date'=>'2026-08-25','due_time'=>'09:00:00','recurrence'=>'weekly']);$this->actingAs($user)->patch(route('todos.complete',$todo));$child=Todo::where('recurrence_parent_id',$todo->id)->firstOrFail();$this->assertSame('2026-09-01',$child->due_date->format('Y-m-d'));$this->assertSame('毎週の確認',$child->title);$this->patch(route('todos.reopen',$todo));$this->patch(route('todos.complete',$todo));$this->assertSame(1,Todo::where('recurrence_parent_id',$todo->id)->count());Carbon::setTestNow();}
}

