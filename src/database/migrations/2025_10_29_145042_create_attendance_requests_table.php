<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')
                ->nullable()
                ->constrained('attendances') // attendances(id) を参照
                ->nullOnDelete('cascade'); // 出勤データ削除時に関連申請も削除

            $table->foreignId('user_id')
                ->constrained('users') // users(id) を参照
                ->onDelete('cascade');

            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->tinyInteger('state')->unsigned(); // 状態（例: 1=申請中, 2=承認, 3=却下）
            $table->string('notes', 1024); // 備考
            $table->timestamp('request_date'); // 申請日時（NOT NULL）
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
        Schema::dropIfExists('attendance_requests');
    }
}
