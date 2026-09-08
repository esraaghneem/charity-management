<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('projects', function (Blueprint $table) {
        $table->tinyInteger('status')->default(1); // 0 = غير نشط, 1 = نشط
    });
}

public function down()
{
    Schema::table('projects', function (Blueprint $table) {
        $table->dropColumn('status');
    });
}

};
