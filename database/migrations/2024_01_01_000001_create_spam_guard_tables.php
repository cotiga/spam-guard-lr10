<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banned_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->unique();
            $table->timestamps();
        });

        Schema::create('spam_guard_errors', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('status_code');
            $table->string('url', 255);
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->index(['status_code', 'url']);
            $table->index('ip');
        });

        Schema::create('error_ignored', function (Blueprint $table) {
            $table->id();
            $table->string('pattern');
            $table->timestamps();
        });

        Schema::create('refused_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('form_name')->default('contact');
            $table->string('mel')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('pays', 10)->nullable();
            $table->string('raison');
            $table->timestamps();

            $table->index('ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refused_contacts');
        Schema::dropIfExists('error_ignored');
        Schema::dropIfExists('spam_guard_errors');
        Schema::dropIfExists('banned_ips');
    }
};
