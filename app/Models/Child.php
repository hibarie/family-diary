<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    protected $fillable = ['name', 'birth_date', 'family_id'];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function schoolEvents()
    {
        return $this->hasMany(SchoolEvent::class);
    }
}
