<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolEvent extends Model
{
    protected $fillable = ['family_id', 'child_id', 'family_member_id', 'title', 'event_type', 'event_date', 'memo'];

    protected $casts = ['event_date' => 'date'];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function familyMember()
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
