<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users') // usersテーブルのidを参照
                ->onDelete('cascade'); // ユーザー削除時に関連出勤データも削除

            $table->string('notes', 1024)->nullable(); // 備考（任意）
            $table->timestamp('start_time')->nullable(); // 出勤時刻
            $table->timestamp('end_time')->nullable();   // 退勤時刻
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
