<?php
namespace Database\Factories;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
/** @extends Factory<ActivityLog> */
class ActivityLogFactory extends Factory{
 public function definition():array{return ['user_id'=>User::factory(),'project_id'=>null,'todo_id'=>null,'title'=>fake()->sentence(4),'performed_on'=>fake()->date(),'performed_at'=>null,'duration_minutes'=>fake()->optional()->numberBetween(5,240),'condition'=>null,'symptoms'=>null,'memo'=>fake()->optional()->paragraph()];}
}
