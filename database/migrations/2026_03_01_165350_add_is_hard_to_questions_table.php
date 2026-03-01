<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds is_hard boolean column and fixes 2 corrupted question records.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->boolean('is_hard')->default(false)->after('difficulty');
        });

        // Fix corrupted question types
        // Q19: fill_in_blank question with wrong type value
        DB::table('questions')->where('id', 19)->update(['type' => 'fill_in_blank']);
        // Q31: true_false question with wrong type value  
        DB::table('questions')->where('id', 31)->update(['type' => 'true_false']);

        // Sync: set is_hard = true where difficulty = 3
        DB::table('questions')->where('difficulty', 3)->update(['is_hard' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('is_hard');
        });
    }
};
