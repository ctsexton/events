<?php namespace CamSexton\Events\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCamsextonEventsEntries extends Migration
{
    public function up()
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
            $table->primary(['event_id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('camsexton_events_entries');
    }
}
