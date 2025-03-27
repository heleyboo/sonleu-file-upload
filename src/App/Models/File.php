<?php

namespace SonLeu\File\App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use SonLeu\File\App\Contracts\HasFileInterface;
use SonLeu\File\App\Models\Traits\HasFile;

/**
 * Class File
 * @package SonLeu\File\App\Models
 *
 * @property int $id
 * @property string $fileable_type
 * @property int $fileable_id
 * @property string $disk
 * @property string $type
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property string $extension
 * @property int $size
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * Relationships
 * @property-read HasFileInterface $fileable
 *
 * Accessor
 * @property-read string $url
 *
 * Scope
 * @method self|static|Builder type($type)
 */
class File extends Model
{
    protected $fillable = [
        'disk',
        'type',
        'path',
        'original_name',
        'mime_type',
        'extension',
        'size',
    ];

    protected $dates = ['created_at', 'updated_at'];

    protected static function boot()
    {
        parent::boot();

        static::deleted(function (self $item) {
            Storage::disk($item->disk)->delete($item->path);
        });
    }

    /**
     * @return MorphTo|HasFile
     */
    public function fileable()
    {
        return $this->morphTo();
    }

    /**
     * @return string
     */
    public function getUrlAttribute()
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeType(Builder $query, $type)
    {
        return $query->where('type', $type);
    }
}
