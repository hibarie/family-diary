<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolEvent extends Model
{
    protected $fillable = ['family_id', 'child_id', 'title', 'event_type', 'event_date', 'memo'];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function child()
    {
        return $this->belongsTo(Child::class);
    }
}
