<?php namespace CamSexton\Events\Models;

use Model;

/**
 * Model
 */
class Entries extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'camsexton_events_entries';
}
