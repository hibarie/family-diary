<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE school_events MODIFY event_type ENUM('event','belongings','meeting','other','work') NOT NULL DEFAULT 'event'");
    }

    public function down(): void
    {
        DB::statement("UPDATE school_events SET event_type = 'other' WHERE event_type = 'work'");
        DB::statement("ALTER TABLE school_events MODIFY event_type ENUM('event','belongings','meeting','other') NOT NULL DEFAULT 'event'");
    }
};
