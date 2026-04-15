<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'description',
        'price',
        'status',
        'sequential_unlock',
        'cover_image',
        'cover_image_thumbnail'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sequential_unlock' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title);

                // Ensure slug is unique
                $originalSlug = $course->slug;
                $count = 1;
                while (static::withTrashed()->where('slug', $course->slug)->exists()) {
                    $course->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function materials()
    {
        return $this->hasMany(CourseMaterial::class);
    }

    public function orderedMaterials()
    {
        return $this->hasMany(CourseMaterial::class)->orderBy('order')->orderBy('id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function activeEnrollments()
    {
        return $this->hasMany(Enrollment::class)->active();
    }

    /**
     * Check if a user or guest is enrolled in this course
     */
    public function isEnrolled($userOrEmail)
    {
        if ($userOrEmail instanceof User) {
            return $this->enrollments()->active()->where('user_id', $userOrEmail->id)->exists();
        }

        return $this->enrollments()->active()->where('guest_email', $userOrEmail)->exists();
    }

    public function getCoverImageUrlAttribute()
    {
        if ($this->cover_image) {
            return route('app.courses.cover', ['course' => $this->id, 'type' => 'cover']);
        }
        return null;
    }

    public function getThumbnailUrlAttribute()
    {
        if ($this->cover_image_thumbnail) {
            return route('app.courses.cover', ['course' => $this->id, 'type' => 'thumbnail']);
        }
        return null;
    }
}
