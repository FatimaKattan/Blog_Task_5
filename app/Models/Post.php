<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'content',
        'images',
    ];
    
    protected $casts = [
        'images' => 'array',
    ];
    
    public function getImagesUrlAttribute()
    {
        if (empty($this->images)) {
            return [];
        }
    
        return array_map(function ($image) {
            return asset('storage/' . $image);
        }, json_decode($this->images, true));
    }
    
    public static function generateThumbnailUrl($imagePath)
    {
    $basePath = 'storage/posts/';
    $filename = basename($imagePath);
    
    return asset($basePath . 'thumbs/' . $filename);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

}
