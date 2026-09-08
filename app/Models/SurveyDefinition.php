<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyDefinition extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'title', 'description', 'version', 'is_active'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'is_active' => 'boolean'];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('position');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SurveyAssignment::class);
    }
}
