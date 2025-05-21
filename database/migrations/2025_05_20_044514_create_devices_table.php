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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('device_identifier');
            $table->string('name')->nullable();
            $table->string('status');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->string('admin_notes')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
