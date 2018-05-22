<?php namespace CamSexton\Events\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCamsextonEventsCalendar2 extends Migration
{
    public function up()
    {
        Schema::create('camsexton_events_calendar', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('calendar_id')->nullable();
            $table->string('timezone')->nullable();
            $table->dateTime('last_updated')->nullable();
            $table->string('calendar_name')->nullable();
            $table->string('next_sync_token')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('camsexton_events_calendar');
    }
}
