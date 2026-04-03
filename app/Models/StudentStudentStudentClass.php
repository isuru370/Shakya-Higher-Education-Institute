<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentStudentStudentClass extends Model
{
    use HasFactory;

    protected $table = 'student_student_student_classes';

    protected $fillable = [
        'student_id',
        'student_classes_id',
        'class_category_has_student_class_id',
        'status',
        'is_free_card',
        'custom_fee',
        'discount_percentage',
        'discount_type',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'student_classes_id' => 'integer',
        'class_category_has_student_class_id' => 'integer',
        'status' => 'boolean',
        'is_free_card' => 'boolean',
        'custom_fee' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Student
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // Class
    public function studentClass()
    {
        return $this->belongsTo(ClassRoom::class, 'student_classes_id');
    }

    // Category + Default Fee
    public function categoryFee()
    {
        return $this->belongsTo(
            ClassCategoryHasStudentClass::class,
            'class_category_has_student_class_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Accessors (IMPORTANT 🔥)
    |--------------------------------------------------------------------------
    */

    // Final Fee (auto calculate)
    public function getFinalFeeAttribute()
    {
        // Free card
        if ($this->is_free_card) {
            return 0;
        }

        // Custom fee (scholarship)
        if (!is_null($this->custom_fee)) {
            return $this->custom_fee;
        }

        // Default fee
        $defaultFee = optional($this->categoryFee)->fees ?? 0;

        // Discount (half card etc.)
        if (!is_null($this->discount_percentage)) {
            return $defaultFee * (1 - ($this->discount_percentage / 100));
        }

        return $defaultFee;
    }

    // Default fee (from class)
    public function getDefaultFeeAttribute()
    {
        return optional($this->categoryFee)->fees ?? 0;
    }

    // Check if discounted
    public function getIsDiscountedAttribute()
    {
        return $this->is_free_card
            || !is_null($this->custom_fee)
            || !is_null($this->discount_percentage);
    }

    public function classCategoryHasStudentClass()
    {
        return $this->belongsTo(
            ClassCategoryHasStudentClass::class,
            'class_category_has_student_class_id'
        );
    }

    // Human readable fee type
    public function getFeeTypeAttribute()
    {
        if ($this->is_free_card) {
            return 'Free Card';
        }

        if (!is_null($this->custom_fee)) {
            return 'Custom Fee';
        }

        if (!is_null($this->discount_percentage)) {
            return $this->discount_percentage . '% Discount';
        }

        return 'Normal';
    }
}
