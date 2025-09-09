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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['call', 'meeting', 'note', 'email']);
            $table->string('subject');
            $table->text('description')->nullable();
            $table->timestamp('happened_at');
            $table->foreignId('contact_id')->nullable()->constrained('contacts');
            $table->foreignId('deal_id')->nullable()->constrained('deals');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
