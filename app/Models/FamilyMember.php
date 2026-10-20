<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    protected $fillable = ['family_id', 'name', 'role', 'birth_date', 'avatar_path'];

    protected $appends = ['avatar_url'];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? '/storage/' . $this->avatar_path : null;
    }
}
