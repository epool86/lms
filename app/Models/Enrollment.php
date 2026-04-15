<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Enrollment extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'order_reference',
        'amount',
        'payment_method',
        'payment_status',
        'chip_purchase_id',
        'payment_proof',
        'status',
        'progress_percentage',
        'enrolled_at',
        'last_accessed_at',
        'completed_at',
        'expires_at',
        'payment_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'enrolled_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'payment_data' => 'array',
        'progress_percentage' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($enrollment) {
            if (empty($enrollment->order_reference)) {
                $enrollment->order_reference = 'ENR-' . strtoupper(Str::random(10));
            }
        });
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Get the student name (user or guest)
     */
    public function getStudentNameAttribute()
    {
        return $this->user ? $this->user->name : $this->guest_name;
    }

    /**
     * Get the student email (user or guest)
     */
    public function getStudentEmailAttribute()
    {
        return $this->user ? $this->user->email : $this->guest_email;
    }

    /**
     * Check if enrollment is active
     */
    public function isActive()
    {
        return $this->status === 'active' && $this->payment_status === 'paid';
    }

    /**
     * Mark enrollment as paid and active
     */
    public function markAsPaid($paymentData = null)
    {
        $this->update([
            'payment_status' => 'paid',
            'status' => 'active',
            'enrolled_at' => now(),
            'payment_data' => $paymentData,
        ]);
    }

    /**
     * Scope for enrollments that grant access (active or completed).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'completed'])->where('payment_status', 'paid');
    }

    /**
     * Scope for pending enrollments
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'active')->where('payment_status', 'paid')->where('progress_percentage', '<', 100);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function recalculateProgress(): void
    {
        $totalMaterials = $this->course->materials()->count();

        if ($totalMaterials === 0) {
            $this->update(['progress_percentage' => 0]);
            return;
        }

        $completedCount = $this->progress()->where('completed', true)->count();
        $percentage = (int) floor(($completedCount / $totalMaterials) * 100);

        $data = ['progress_percentage' => $percentage];

        if ($percentage >= 100 && $this->status !== 'completed') {
            $data['status'] = 'completed';
            $data['completed_at'] = now();
        } elseif ($percentage < 100 && $this->status === 'completed') {
            $data['status'] = 'active';
            $data['completed_at'] = null;
        }

        $this->update($data);
    }

    public function lastAccessedMaterial(): ?CourseMaterial
    {
        $progress = $this->progress()
            ->whereNotNull('last_accessed_at')
            ->orderByDesc('last_accessed_at')
            ->first();

        if ($progress) {
            return $progress->material;
        }

        return $this->course->materials()->orderBy('order')->orderBy('id')->first();
    }
}
