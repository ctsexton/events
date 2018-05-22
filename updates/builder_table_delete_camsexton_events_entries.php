<?php namespace CamSexton\Events\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteCamsextonEventsEntries extends Migration
{
    public function up()
    {
        Schema::dropIfExists('camsexton_events_entries');
    }
    
    public function down()
    {
        Schema::create('camsexton_events_entries', function($table)
        {
            $table->engine = 'InnoDB';
            $table->string('event_id');
            $table->string('title')->nullable();
            $table->dateTime('date_time')->nullable();
            $table->string('venue')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('event_timezone')->nullable();
            $table->primary(['event_id']);
        });
    }
}
