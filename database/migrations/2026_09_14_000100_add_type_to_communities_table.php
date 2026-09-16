<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            // Communities & Partner Schools (6.3 amendment): one registry,
            // two record types. Existing rows default to 'community'.
            $table->string('type', 20)->default('community')->index()->after('province');
            $table->string('school_level', 20)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'school_level']);
        });
    }
};
