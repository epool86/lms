<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $fillable = [
        'enrollment_id',
        'course_material_id',
        'completed',
        'completed_at',
        'time_spent',
        'last_position',
        'last_accessed_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'time_spent' => 'integer',
        'last_position' => 'integer',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function material()
    {
        return $this->belongsTo(CourseMaterial::class, 'course_material_id');
    }

    public function markCompleted(): void
    {
        if ($this->completed) {
            return;
        }

        $this->update([
            'completed' => true,
            'completed_at' => now(),
        ]);
    }
}
