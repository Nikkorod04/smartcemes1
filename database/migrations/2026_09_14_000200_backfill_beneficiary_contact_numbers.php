<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Contact-number feature (blueprint v4.7): surface the existing 6.6 phone
     * column as "Contact number" and seed the demo default on every
     * beneficiary that has none.
     */
    public function up(): void
    {
        DB::table('beneficiaries')
            ->whereNull('phone')
            ->update(['phone' => '09123456789']);
    }

    public function down(): void
    {
        DB::table('beneficiaries')
            ->where('phone', '09123456789')
            ->update(['phone' => null]);
    }
};
