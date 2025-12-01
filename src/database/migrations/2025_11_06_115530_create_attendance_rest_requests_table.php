<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceRestRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_rest_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')
                ->constrained('attendance_requests') // attendance_requests(id) を参照
                ->onDelete('cascade'); // データ削除時に関連申請も削除

            $table->foreignId('rest_id')
                ->nullable()
                ->constrained('attendance_rests') // attendance_rests(id) を参照
                ->onDelete('cascade');

            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
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
        Schema::dropIfExists('attendance_rest_requests');
    }
}
