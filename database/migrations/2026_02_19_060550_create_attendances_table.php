<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');

        $table->datetime('clock_in')->nullable();
        $table->datetime('clock_out')->nullable();

        $table->text('clockin_description')->nullable();
        $table->text('clockout_description')->nullable();

        $table->boolean('auto_checkout')->default(0);
        $table->boolean('is_absent')->default(0);

        $table->integer('work_minutes')->default(0);

        $table->date('attendance_date');

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
