<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        if (Schema::hasColumn('people', 'phone_normalized')) {
            Schema::table('people', function (Blueprint $table) {
                $table->dropIndex(['phone_normalized']);
                $table->dropColumn('phone_normalized');
            });
        }

        // SQLite no permite ALTER ADD de una columna STORED. VIRTUAL sigue siendo generada.
        Schema::table('people', function (Blueprint $table) {
            $table->string('phone_normalized', 32)->nullable()->virtualAs($this->expression());
            $table->index('phone_normalized');
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex(['phone_normalized']);
            $table->dropColumn('phone_normalized');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->string('phone_normalized', 32)->nullable()->index();
        });
    }

    private function expression(): string
    {
        $digits = "COALESCE(NULLIF(phone_raw, ''), NULLIF(phone_e164, ''), '')";
        foreach (['+', ' ', '-', '(', ')', '.', '/'] as $char) {
            $digits = "replace({$digits}, '{$char}', '')";
        }

        return "CASE WHEN {$digits} = '' THEN NULL WHEN length({$digits}) = 10 AND substr({$digits}, 1, 1) = '0' THEN '593' || substr({$digits}, 2) WHEN length({$digits}) = 9 AND substr({$digits}, 1, 1) = '9' THEN '593' || {$digits} ELSE {$digits} END";
    }
};
