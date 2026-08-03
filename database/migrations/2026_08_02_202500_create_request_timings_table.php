<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_timings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('method', 10);
            $table->string('path');
            $table->string('route')->nullable();
            $table->integer('duration_ms');
            $table->integer('status_code')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index('path');
            $table->index('route');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_timings');
    }
};
