<?php
namespace App\Models;
use App\Policies\ActivityLogPolicy;
use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
#[Fillable(['user_id','project_id','todo_id','treatment_id','title','performed_on','performed_at','duration_minutes','condition','symptoms','memo'])]
#[UseFactory(ActivityLogFactory::class)]
#[UsePolicy(ActivityLogPolicy::class)]
class ActivityLog extends Model{
 use HasFactory;
 protected function casts():array{return ['performed_on'=>'date','duration_minutes'=>'integer','symptoms'=>'array'];}
 public function user():BelongsTo{return $this->belongsTo(User::class);}
 public function project():BelongsTo{return $this->belongsTo(Project::class);}
 public function todo():BelongsTo{return $this->belongsTo(Todo::class);}
 public function treatment():BelongsTo{return $this->belongsTo(Treatment::class);}
 public function photos():HasMany{return $this->hasMany(ActivityLogPhoto::class);}
}