<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiaryEntry extends Model
{
    protected $fillable = ['family_id', 'user_id', 'title', 'body', 'entry_date'];

    protected $casts = ['entry_date' => 'date'];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
