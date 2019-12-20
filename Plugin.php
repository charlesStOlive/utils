<?php namespace Waka\Utils;

use Backend;
use Event;
use View;
use System\Classes\PluginBase;
use Lang;
use Waka\Utils\Columns\BtnActions;


/**
 * Utils Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Utils',
            'description' => 'No description provided yet...',
            'author'      => 'Waka',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommand('waka.model', 'Waka\Utils\Console\CreateModel');
        $this->registerConsoleCommand('waka.injector', 'Waka\Utils\Console\CreateInjector');
        $this->registerConsoleCommand('waka.controller', 'Waka\Utils\Console\CreateController');
        $this->registerConsoleCommand('waka.behaviorcontent', 'Waka\Utils\Console\CreateBehaviorContent');

    }

    public function registerListColumnTypes()
    {
        return [
            'waka-btn-actions' => [BtnActions::class, 'render'],
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        Event::listen('backend.top.update', function($controller) {
            trace_log($controller->implement);
            if(in_array('Waka.Utils.Behaviors.DuplicateModel', $controller->implement )) {
                $model = $controller->formGetModel();
                return View::make('waka.utils::duplicatebutton')->withId($model->id);
            }
            
        });
        Event::listen('popup.actions.line1', function($controller, $model, $id) {
            trace_log($model);
            trace_log($id);
            if(in_array('Waka.Utils.Behaviors.DuplicateModel', $controller->implement )) {
                return View::make('waka.utils::duplicatebutton')->withId($id);
            }
            
        });
        

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Waka\Utils\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'waka.utils.some_permission' => [
                'tab' => 'Utils',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerSettings()
    {
        return [
            'data_sources' => [
                'label'       => Lang::get('waka.utils::lang.settings_ds.label'),
                'description' => Lang::get('waka.utils::lang.settings_ds.description'),
                'category'    => Lang::get('waka.utils::lang.settings_ds.category'),
                'icon'        => 'icon-paper-plane',
                'url'         => Backend::url('waka/utils/datasources'),
                'order'       => 1,
            ]
        ];
    }
}