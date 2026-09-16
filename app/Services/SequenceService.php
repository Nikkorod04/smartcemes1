<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SequenceService
{
    /**
     * Atomically reserve the next integer for a named sequence.
     *
     * Race-safe: the sequence row is ensured with insertOrIgnore (portable
     * INSERT IGNORE semantics) followed by SELECT ... FOR UPDATE inside a
     * transaction, so concurrent callers serialize instead of
     * read-max-then-write.
     *
     * $floor heals drift caused by rows created outside the sequence
     * (seeders hardcode codes, failed inserts still consume a value):
     * the reserved value is max(sequence row, floor) + 1, so a stale or
     * missing row can never hand out a code the table already holds. The
     * locked row remains the serializer — the floor only ever raises it.
     */
    public function next(string $key, ?int $floor = null): int
    {
        return DB::transaction(function () use ($key, $floor) {
            DB::table('sequences')->insertOrIgnore(['key' => $key, 'last_value' => 0]);

            $row = DB::table('sequences')
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            $next = max((int) $row->last_value, $floor ?? 0) + 1;

            DB::table('sequences')->where('key', $key)->update(['last_value' => $next]);

            return $next;
        });
    }
}
