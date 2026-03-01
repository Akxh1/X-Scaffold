<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add prediction_source column to student_module_performance table.
     * Also delete fill_in_blank questions and their answers.
     */
    public function up(): void
    {
        Schema::table('student_module_performance', function (Blueprint $table) {
            $table->string('prediction_source')->nullable()->after('xai_explanation');
        });

        // Delete fill_in_blank questions and their answers
        $fillInBlankIds = DB::table('questions')->where('type', 'fill_in_blank')->pluck('id');
        if ($fillInBlankIds->count() > 0) {
            DB::table('answers')->whereIn('question_id', $fillInBlankIds)->delete();
            DB::table('questions')->whereIn('id', $fillInBlankIds)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_module_performance', function (Blueprint $table) {
            $table->dropColumn('prediction_source');
        });
    }
};
