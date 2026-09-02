<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the core tables with the remaining fee-tracking fields:
 *
 *  - students: student_code (unique), grade, class_time
 *  - fees:     status (denormalised payment status of the fee period)
 *
 * The `fees` table is the fee-period table: one row per student per
 * billing month (enforced by the unique [student_id, period_month]
 * constraint added in an earlier migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_code', 30)->nullable()->after('id');
            $table->string('grade', 50)->nullable()->after('batch');
            $table->string('class_time', 100)->nullable()->after('grade');
        });

        // Backfill a unique code for any students created before this migration.
        DB::table('students')
            ->whereNull('student_code')
            ->orderBy('id')
            ->get()
            ->each(function ($student): void {
                DB::table('students')
                    ->where('id', $student->id)
                    ->update(['student_code' => 'STU-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT)]);
            });

        // Now that every row has a code, make the column mandatory.
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_code', 30)->nullable(false)->change();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->unique('student_code');
        });

        Schema::table('fees', function (Blueprint $table) {
            $table->string('status', 20)->default('unpaid')->after('amount');
            $table->index('status');
        });

        // Backfill the stored status from existing payments so the new
        // column is correct for pre-existing fee periods as well.
        DB::table('fees')->get()->each(function ($fee): void {
            $paid = (float) DB::table('payments')->where('fee_id', $fee->id)->sum('amount');

            $status = match (true) {
                $paid <= 0.0 => 'unpaid',
                $paid >= (float) $fee->amount => 'paid',
                default => 'partial',
            };

            DB::table('fees')->where('id', $fee->id)->update(['status' => $status]);
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['student_code']);
            $table->dropColumn(['student_code', 'grade', 'class_time']);
        });
    }
};
