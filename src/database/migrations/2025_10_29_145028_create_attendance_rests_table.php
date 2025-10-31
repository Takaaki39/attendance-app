<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceRestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_rests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')
                ->constrained('attendances') // attendancesテーブルのidを参照
                ->onDelete('cascade'); // 削除時に関連出勤データも削除
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
        Schema::dropIfExists('attendance_rests');
    }
}
