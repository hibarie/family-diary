<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = ['disk', 'path', 'type'];

    protected $appends = ['url'];

    public function mediable()
    {
        return $this->morphTo();
    }

    /**
     * フロントエンドがそのまま<img>/<video>のsrcに使える公開URL。
     */
    public function getUrlAttribute(): string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);

        return $disk->url($this->path);
    }
}
