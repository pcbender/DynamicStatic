<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // one project per user
            $table->string('name', 150);
            $table->string('owner', 100);
            $table->string('repo', 100);
            $table->string('github_app_id', 50); // numeric but stored as string
            $table->string('github_app_client_id', 100);
            $table->timestamps();
            $table->index(['owner','repo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
