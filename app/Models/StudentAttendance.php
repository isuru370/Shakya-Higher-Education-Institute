<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $table = "student_attendances";

    protected $fillable = [
        'at_date',
        'student_id',
        'attendance_id',
    ];

    protected $casts = [
        'student_id'    => 'integer',
        'attendance_id' => 'integer',
        'at_date'       => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    // 🔗 Student relationship
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // 🔗 Class attendance (session)
    public function classAttendance()
    {
        return $this->belongsTo(ClassAttendance::class, 'attendance_id');
    }
}
