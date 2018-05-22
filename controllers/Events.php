<?php namespace CamSexton\Events\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Events extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController'    ];
    
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('CamSexton.Events', 'main-menu-item', 'side-menu-item2');
    }
}
