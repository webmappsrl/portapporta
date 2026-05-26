<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trash_types', function (Blueprint $table) {
            $table->text('confirmation_message')->nullable()->after('show_in_report');
        });
    }

    public function down(): void
    {
        Schema::table('trash_types', function (Blueprint $table) {
            $table->dropColumn('confirmation_message');
        });
    }
};
