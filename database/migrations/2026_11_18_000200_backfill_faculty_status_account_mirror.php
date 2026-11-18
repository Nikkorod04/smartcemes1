<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One-time mirror repair: before this deploy, users.is_active (the login
     * gate, toggled in User Management) and faculties.status (the directory's
     * own column) could drift apart. From now on both sides sync
     * (Users\Index::changeStatus <-> Faculty\Directory::save), and this
     * backfills the existing drift:
     *   - a deactivated faculty LOGIN reads "Inactive" in the directory;
     *   - a directory-"Inactive" faculty member has their login locked.
     */
    public function up(): void
    {
        DB::table('faculties')
            ->join('users', 'users.id', '=', 'faculties.user_id')
            ->where('users.is_active', false)
            ->where('faculties.status', '!=', 'inactive')
            ->update(['faculties.status' => 'inactive']);

        DB::table('users')
            ->join('faculties', 'faculties.user_id', '=', 'users.id')
            ->where('users.role', 'faculty')
            ->where('faculties.status', 'inactive')
            ->where('users.is_active', true)
            ->update(['users.is_active' => false]);
    }

    public function down(): void
    {
        // The drift this repairs was never intentional state, so there is
        // nothing to restore.
    }
};
