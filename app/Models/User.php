<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'roles',
        'company_name',
        'logo',
        'plan',
        'validity',
        'suspended_at',
        'suspension_reason',
        'trainer_settings',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'trainer_settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'roles' => 'array',
            'validity' => 'date',
            'suspended_at' => 'datetime',
            'trainer_settings' => 'array',
        ];
    }

    /**
     * Get the user's roles.
     *
     * @return array
     */
    protected function roles(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? json_decode($value, true) : ['student'],
            set: fn ($value) => json_encode($value ?? ['student'])
        );
    }

    /**
     * Get a trainer setting value.
     */
    public function getTrainerSetting(string $key, $default = null)
    {
        return $this->trainer_settings[$key] ?? $default;
    }

    /**
     * Set a trainer setting value.
     */
    public function setTrainerSetting(string $key, $value): void
    {
        $settings = $this->trainer_settings ?? [];
        $settings[$key] = $value;
        $this->trainer_settings = $settings;
    }

    /**
     * Get the courses created by this user (trainer).
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Get the enrollments for this user (student).
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function activeEnrollments()
    {
        return $this->hasMany(Enrollment::class)->active();
    }

    /**
     * Get the enrolled courses for this user.
     */
    public function enrolledCourses()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->wherePivotIn('status', ['active', 'completed'])
            ->wherePivot('payment_status', 'paid');
    }

    /**
     * Check if user is enrolled in a course.
     */
    public function isEnrolledIn(Course $course)
    {
        return $this->enrollments()
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->where('payment_status', 'paid')
            ->exists();
    }

    public function isSuspended(): bool
    {
        return ! is_null($this->suspended_at);
    }
}
