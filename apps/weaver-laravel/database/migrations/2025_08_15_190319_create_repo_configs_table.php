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
        Schema::create('repo_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repo_id')->constrained('repos')->onDelete('cascade');
            $table->string('branch');
            $table->string('base_path');
            $table->string('deploy_target');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repo_configs');
    }
};
