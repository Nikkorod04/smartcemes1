<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The budget entry's accountability field becomes ATTACHMENTS: up to 5
     * proof-of-expense files (pdf/jpg/png/docx/xlsx, 10 MB each) stored
     * on the public disk, replacing the free-text receipt_reference (which
     * had no input anywhere in the UI and was always auto-generated).
     */
    public function up(): void
    {
        Schema::table('budget_utilizations', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('description');
            $table->dropColumn('receipt_reference');
        });
    }

    public function down(): void
    {
        Schema::table('budget_utilizations', function (Blueprint $table) {
            $table->string('receipt_reference')->nullable()->after('description');
            $table->dropColumn('attachments');
        });
    }
};
