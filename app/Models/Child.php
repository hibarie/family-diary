<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    protected $fillable = ['name', 'birth_date', 'family_id', 'avatar_path'];

    // JSONに avatar_url(顔写真のURL)も含める
    protected $appends = ['avatar_url'];

    /**
     * 顔写真のURL。APP_URL の設定に左右されないよう、サイト内の相対パスで返す。
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? '/storage/'.$this->avatar_path : null;
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function schoolEvents()
    {
        return $this->hasMany(SchoolEvent::class);
    }
}
