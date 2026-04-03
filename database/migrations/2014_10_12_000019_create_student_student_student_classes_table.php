<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentStudentStudentClassesTable extends Migration
{
    public function up()
    {
        Schema::create('student_student_student_classes', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Student
            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            // Class
            $table->foreignId('student_classes_id')
                ->constrained('student_classes')
                ->cascadeOnDelete();

            // Class + Category + Default Fee link
            $table->foreignId('class_category_has_student_class_id')
                ->constrained('class_category_has_student_class')
                ->cascadeOnDelete();

            // Active / inactive enrollment
            $table->boolean('status')->default(true);

            // Free card (100% free)
            $table->boolean('is_free_card')->default(false);

            // 🔥 NEW: Custom fee (scholarship etc.)
            $table->decimal('custom_fee', 10, 2)->nullable();

            // 🔥 NEW: Discount percentage (half card = 50)
            $table->decimal('discount_percentage', 5, 2)->nullable();

            // 🔥 NEW: Reason / type (optional but useful)
            $table->string('discount_type')->nullable();
            // examples: scholarship, half_card, staff_child

            $table->timestamps();

            // ❗ Prevent duplicate same student same class same category
            $table->unique([
                'student_id',
                'student_classes_id',
                'class_category_has_student_class_id'
            ], 'unique_student_class_category');
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_student_student_classes');
    }
}
