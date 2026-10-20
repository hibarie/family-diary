<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_events', function (Blueprint $table) {
            // パパ・ママの予定も入れられるように、child_id は空でもよくする
            $table->unsignedBigInteger('child_id')->nullable()->change();
            $table->foreignId('family_member_id')->nullable()->after('child_id')
                ->constrained('family_members')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('school_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('family_member_id');
        });
    }
};
