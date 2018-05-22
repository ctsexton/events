<?php namespace CamSexton\Events\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCamsextonEventsEntries2 extends Migration
{
    public function up()
    {
        Schema::create('camsexton_events_entries', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('event_id')->nullable();
            $table->string('title')->nullable();
            $table->dateTime('date_time')->nullable();
            $table->string('venue')->nullable();
            $table->text('description')->nullable();
            $table->string('event_timezone')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('camsexton_events_entries');
    }
}
