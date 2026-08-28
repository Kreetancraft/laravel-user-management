<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make `users.password` nullable.
 *
 * The invitation flow deliberately creates a user with NO password — they set
 * their own via the emailed link. Laravel's stock `create_users_table` declares
 * `password` NOT NULL, and because it shares a filename with this package's own
 * users migration the host's version is the one that runs. Without this,
 * inviting a user fails with a NOT NULL constraint violation on any app using
 * the standard skeleton.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'password')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Not reversed on purpose.
     *
     * Rows created by an invitation legitimately have a null password, so
     * re-imposing NOT NULL would fail against real data. Restore it yourself
     * only once you know every row has one.
     */
    public function down(): void
    {
        // Intentionally irreversible — see the docblock above.
    }
};
