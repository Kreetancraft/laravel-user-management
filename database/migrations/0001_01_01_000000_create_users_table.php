<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->string('invitation_token', 500)->nullable()->index();
                $table->timestamp('invitation_sent_at')->nullable();
                $table->boolean('enforce_2fa')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_login_at')->nullable();
                $table->string('last_login_ip', 45)->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'invitation_token')) {
                    $table->string('invitation_token', 500)->nullable()->index()->after('password');
                }
                if (! Schema::hasColumn('users', 'invitation_sent_at')) {
                    $table->timestamp('invitation_sent_at')->nullable()->after('invitation_token');
                }
                if (! Schema::hasColumn('users', 'enforce_2fa')) {
                    $table->boolean('enforce_2fa')->default(false)->after('invitation_sent_at');
                }
                if (! Schema::hasColumn('users', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('enforce_2fa');
                }
                if (! Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('is_active');
                }
                if (! Schema::hasColumn('users', 'last_login_ip')) {
                    $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
                }
                if (! Schema::hasColumn('users', 'deleted_at')) {
                    $table->softDeletes()->after('remember_token');
                }
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        // Do not drop password_reset_tokens/sessions if they were pre-existing
    }
};
