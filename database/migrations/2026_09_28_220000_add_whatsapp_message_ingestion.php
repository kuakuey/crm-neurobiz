<?php

use App\Support\PhoneNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();
        foreach (['WhatsApp', 'Instagram', 'Facebook', 'Email'] as $name) {
            DB::table('lead_sources')->insert([
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('channels')->insert([
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('people', function (Blueprint $table) {
            $table->foreignId('source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->string('next_action')->nullable();
            $table->timestamp('next_action_at')->nullable();

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $digits = "REGEXP_REPLACE(COALESCE(NULLIF(`phone_raw`, ''), NULLIF(`phone_e164`, ''), ''), '[^0-9]', '')";
                $expression = "CASE WHEN {$digits} = '' THEN NULL WHEN {$digits} REGEXP '^0[0-9]{9}$' THEN CONCAT('593', SUBSTRING({$digits}, 2)) WHEN {$digits} REGEXP '^9[0-9]{8}$' THEN CONCAT('593', {$digits}) ELSE {$digits} END";
                $table->string('phone_normalized', 32)->nullable()->storedAs($expression);
            } else {
                $table->string('phone_normalized', 32)->nullable();
            }

            $table->index('phone_normalized');
        });

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            DB::table('people')->select('id', 'phone_raw', 'phone_e164')->orderBy('id')->each(function ($row) {
                DB::table('people')->where('id', $row->id)->update([
                    'phone_normalized' => PhoneNormalizer::normalized($row->phone_raw ?: $row->phone_e164),
                ]);
            });
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('channel_id')->nullable()->constrained('channels')->nullOnDelete();
            $table->string('external_ref')->nullable();
            // NULL se puede repetir en MySQL y SQLite; el valor no nulo es único.
            $table->unique('external_ref');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropUnique(['external_ref']);
            $table->dropConstrainedForeignId('channel_id');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex(['phone_normalized']);
            $table->dropColumn(['phone_normalized', 'next_action', 'next_action_at']);
            $table->dropConstrainedForeignId('source_id');
        });

        Schema::dropIfExists('channels');
        Schema::dropIfExists('lead_sources');
    }
};
