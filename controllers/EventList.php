<?php namespace CamSexton\Events\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class EventList extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController',        'Backend\Behaviors\ReorderController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('CamSexton.Events', 'main-menu-item', 'side-menu-item2');
    }
}
