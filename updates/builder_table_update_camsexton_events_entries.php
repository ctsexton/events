<?php namespace CamSexton\Events\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCamsextonEventsEntries extends Migration
{
    public function up()
    {
        Schema::table('camsexton_events_entries', function($table)
        {
            $table->string('event_timezone')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('camsexton_events_entries', function($table)
        {
            $table->dropColumn('event_timezone');
        });
    }
}
