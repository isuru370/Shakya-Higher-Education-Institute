<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentAttendancesTable extends Migration
{
    public function up()
    {
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->dateTime('at_date');

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('attendance_id')
                ->constrained('class_attendances')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['student_id', 'attendance_id'], 'unique_student_attendance');
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_attendances');
    }
}