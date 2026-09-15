<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    protected $fillable = ['name', 'invite_code'];

    public function children()
    {
        return $this->hasMany(Child::class);
    }

    public function diaryEntries()
    {
        return $this->hasMany(DiaryEntry::class);
    }
}
