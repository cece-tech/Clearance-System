<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clearance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'section',
        'request_type',
        'status',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the student that owns the clearance
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the approvers for the clearance
     */
    public function approvers(): HasMany
    {
        return $this->hasMany(ClearanceApprover::class);
    }

    /**
     * Get the documents for the clearance
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ClearanceDocument::class);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by request type
     */
    public function scopeRequestType($query, $type)
    {
        return $query->where('request_type', $type);
    }

    /**
     * Check if clearance is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if clearance is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if clearance is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

// app/Models/ClearanceApprover.php
class ClearanceApprover extends Model
{
    use HasFactory;

    protected $fillable = [
        'clearance_id',
        'approver_role',
        'approver_name',
        'order',
        'status',
        'remarks',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the clearance that owns the approver
     */
    public function clearance(): BelongsTo
    {
        return $this->belongsTo(Clearance::class);
    }

    /**
     * Get the user (approver)
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Check if approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}

// app/Models/Student.php
class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'name',
        'email',
        'year_level',
        'section',
        'program',
        'contact_number',
    ];

    /**
     * Get the clearances for the student
     */
    public function clearances(): HasMany
    {
        return $this->hasMany(Clearance::class);
    }
}