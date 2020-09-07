<?php namespace Waka\Utils\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateJobListsTable extends Migration
{
    public function up()
    {
        Schema::create('waka_utils_job_lists', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('state')->nullable();
            $table->integer('attempts')->nullable();
            $table->text('payload')->nullable();
            $table->text('errors')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_utils_job_lists');
    }
}