<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseMaterial extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'visibility',
        'order',
        'is_preview',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'order' => 'integer',
        'is_preview' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function isAccessibleFor(Enrollment $enrollment): bool
    {
        if ($this->is_preview) {
            return true;
        }

        if (! $this->course->sequential_unlock) {
            return true;
        }

        $previousIds = $this->course->orderedMaterials()
            ->where(function ($q) {
                $q->where('order', '<', $this->order)
                  ->orWhere(function ($q2) {
                      $q2->where('order', $this->order)->where('id', '<', $this->id);
                  });
            })
            ->pluck('id');

        if ($previousIds->isEmpty()) {
            return true;
        }

        $completedCount = $enrollment->progress()
            ->whereIn('course_material_id', $previousIds)
            ->where('completed', true)
            ->count();

        return $completedCount === $previousIds->count();
    }

    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
