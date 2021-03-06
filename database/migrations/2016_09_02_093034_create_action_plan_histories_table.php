<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActionPlanHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('action_plan_histories')) {
            Schema::create('action_plan_histories', function (Blueprint $table) {
                $table->increments('action_plan_history_id');
                $table->integer('action_plan_id');
                $table->integer('approval_type_id');
                $table->string('action_plan_history_text');
                $table->enum('active', ['0', '1'])->default('1');
                $table->integer('created_by');
                $table->integer('updated_by');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('action_plan_histories');
    }
}
