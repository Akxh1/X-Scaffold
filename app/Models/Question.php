<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_hard' => 'boolean',
        'difficulty' => 'integer',
    ];

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Scope: only hard questions (is_hard=true OR difficulty >= 3)
     */
    public function scopeHard($query)
    {
        return $query->where('is_hard', true);
    }

    /**
     * Scope: only non-hard questions
     */
    public function scopeNotHard($query)
    {
        return $query->where(function ($q) {
            $q->where('is_hard', false)->orWhereNull('is_hard');
        });
    }
}
