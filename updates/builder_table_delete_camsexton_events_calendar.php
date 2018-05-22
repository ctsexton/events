<?php namespace CamSexton\Events\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteCamsextonEventsCalendar extends Migration
{
    public function up()
    {
        Schema::dropIfExists('camsexton_events_calendar');
    }
    
    public function down()
    {
        Schema::create('camsexton_events_calendar', function($table)
        {
            $table->engine = 'InnoDB';
            $table->string('calendar_id');
            $table->string('timezone')->nullable();
            $table->dateTime('last_updated')->nullable();
            $table->string('calendar_name')->nullable();
            $table->string('next_sync_token')->nullable();
            $table->primary(['calendar_id']);
        });
    }
}
