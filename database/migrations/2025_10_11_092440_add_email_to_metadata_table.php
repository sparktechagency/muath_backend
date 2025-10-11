<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('metadata', function (Blueprint $table) {
            $table->string('email')->after('user_id');  // Adding the email field
        });
    }

    public function down()
    {
        Schema::table('metadata', function (Blueprint $table) {
            $table->dropColumn('email');  // Drop email field if rolled back
        });
    }

};
