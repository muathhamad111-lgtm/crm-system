<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('approval_status')->nullable()->index(); // pending|approved|rejected
            $table->timestamp('approval_requested_at')->nullable();
            $table->char('approval_requested_by', 36)->nullable();
            $table->timestamp('approval_decided_at')->nullable();
            $table->char('approval_decided_by', 36)->nullable();
            $table->text('approval_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn([
                'approval_status', 'approval_requested_at', 'approval_requested_by',
                'approval_decided_at', 'approval_decided_by', 'approval_notes',
            ]);
        });
    }
};
