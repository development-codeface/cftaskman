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
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')   // references id of users table
                ->onDelete('cascade');
            $table->foreignId('project_id')
                ->constrained('projects')   // references id of users table
                ->onDelete('cascade');
            $table->foreignId('task_id')
                ->constrained('tasks')   // references id of users table
                ->onDelete('cascade');
            $table->date('work_date')->nullable();
            $table->decimal('hours', 5, 2)->default(0.00);
            $table->longText('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_logs');
    }
};
