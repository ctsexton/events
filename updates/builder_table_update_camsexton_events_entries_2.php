<?php namespace CamSexton\Events\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCamsextonEventsEntries2 extends Migration
{
    public function up()
    {
        Schema::table('camsexton_events_entries', function($table)
        {
            $table->string('file_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('camsexton_events_entries', function($table)
        {
            $table->dropColumn('file_id');
        });
    }
}
