<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $registrations = DB::table('registrations')->select('id', 'email')->get();

        foreach ($registrations as $registration) {
            DB::table('registrations')->where('id', $registration->id)->update([
                'email' => strtolower(trim((string) $registration->email)),
            ]);
        }

        $duplicateEmails = DB::table('registrations')
            ->select('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('email');

        if ($duplicateEmails->isNotEmpty()) {
            throw new \RuntimeException(
                'Cannot add a unique email constraint because duplicate emails already exist: '.$duplicateEmails->implode(', ')
            );
        }

        $emailIndexes = collect(Schema::getIndexes('registrations'))
            ->filter(fn (array $index) => in_array('email', $index['columns'], true));

        Schema::table('registrations', function (Blueprint $table) use ($emailIndexes) {
            foreach ($emailIndexes as $index) {
                if ($index['unique'] ?? false) {
                    continue;
                }

                $table->dropIndex($index['name']);
            }
        });

        $alreadyUnique = collect(Schema::getIndexes('registrations'))
            ->contains(fn (array $index) => in_array('email', $index['columns'], true) && ($index['unique'] ?? false));

        if (! $alreadyUnique) {
            Schema::table('registrations', function (Blueprint $table) {
                $table->unique('email');
            });
        }
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->index('email');
        });
    }
};
