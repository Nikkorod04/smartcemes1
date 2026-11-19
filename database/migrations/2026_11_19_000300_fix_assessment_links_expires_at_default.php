<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `expires_at` was created as `timestamp NOT NULL` with no default. On MySQL
     * with `explicit_defaults_for_timestamp = OFF` (the XAMPP and many shared
     * hosts' default), the *first* such TIMESTAMP column is silently given
     * `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`.
     *
     * The visible effect: every UPDATE to a link — revoking it, or anything
     * else — rewrote `expires_at` to "now", so a link revoked on day one read as
     * expired, and an extended link lost its new expiry the next time any other
     * column was touched. SQLite ignores the quirk, so the test suite stayed
     * green while production behaved differently.
     *
     * Making the column nullable removes the implicit default/ON UPDATE. The
     * application always sets it; null simply means "no expiry recorded".
     */
    public function up(): void
    {
        Schema::table('assessment_links', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_links', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable(false)->change();
        });
    }
};
