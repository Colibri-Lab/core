<?php

namespace Colibri\Security;

use Colibri\App;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;

/**
 * @testFunction testSecurityManager
 */
class SecurityManager
{
    use TEventDispatcher;

    public static $instance;

    private $_permissions;
    private $_permissionsTree;

    public function __construct()
    {
        $this->_permissions = [];
        $this->_permissionsTree = [];
    }

    /**
     * @testFunction testSecurityManagerCreate
     */
    public static function Create()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @testFunction testSecurityManagerGetPermissions
     */
    public function GetPermissions()
    {
        $permissions = [];

        $permissions['app.security'] = 'Система безопасности';
        $permissions['app.security.manage'] = 'Управление безопасностью';
        $permissions['app.security.roles'] = 'Роли';
        $permissions['app.security.roles.list'] = 'Отображение списка ролей';
        $permissions['app.security.roles.add'] = 'Добавление роли';
        $permissions['app.security.roles.remove'] = 'Удаление роли';
        $permissions['app.security.roles.edit'] = 'Редактировние роли';
        $permissions['app.security.users'] = 'Пользователи';
        $permissions['app.security.users.list'] = 'Отображение списка пользователей';
        $permissions['app.security.users.add'] = 'Добавление пользователя';
        $permissions['app.security.users.remove'] = 'Удаление пользователя';
        $permissions['app.security.users.edit'] = 'Редактирование пользователя';

        return $permissions;
    }

    /**
     * @testFunction testSecurityManagerInitialize
     */
    public function Initialize()
    {

        App::$monitoring->StartTimer('permissions');
        // подключение прав приложения
        if (is_object(App::$instance) && method_exists(App::$instance, 'GetPermissions')) {
            $this->_permissions = array_merge($this->_permissions, App::$instance->GetPermissions());
        }

        // подключение прав безопасности
        if (method_exists($this, 'GetPermissions')) {
            $this->_permissions = array_merge($this->_permissions, $this->GetPermissions());
        }

        if (is_object(App::$moduleManager) && method_exists(App::$moduleManager, 'GetPermissions')) {
            $this->_permissions = array_merge($this->_permissions, App::$moduleManager->GetPermissions());
        }

        // подключение прав модулей
        foreach (App::$moduleManager->list as $module) {
            if (is_object($module) && method_exists($module, 'GetPermissions')) {
                $this->_permissions = array_merge($this->_permissions, $module->GetPermissions());
            }
        }

        $this->_permissionsTree = $this->CreatePermissionsTree();

        App::$monitoring->EndTimer('permissions');

        $this->DispatchEvent(EventsContainer::SecurityManagerReady);
    }

    /**
     * @testFunction testSecurityManagerCreatePermissionsTree
     */
    public function CreatePermissionsTree()
    {

        App::$monitoring->StartTimer('permissions-tree');

        $tree = ['children' => []];
        foreach ($this->_permissions as $permission => $description) {

            $permission = explode('.', $permission);
            $evalCode = '$tree';
            foreach ($permission as $p) {
                $evalCode .= '["children"]["' . $p . '"]';
            }
            $evalCode .= ' = ["permission" => "'.$p.'", "title" => "' . $description . '", "children" => []];';
            eval($evalCode);
        }
        App::$monitoring->EndTimer('permissions-tree');
        return $tree['children'];
    }

    public function IsCommandAllowed($permissions, $command)
    {
        
        uasort($permissions, function($a, $b) {
            if($a['path'] < $b['path']) {
                return 1;
            }
            else if($a['path'] < $b['path']) {
                return -1;
            }
            else {
                return 0;
            }
        });

        foreach ($permissions as $perm) {
            $permission = str_replace('*', '.*', str_replace('.', '\.', $perm['path']));
            if (preg_match('/'.$permission.'$/im', $command, $matches) > 0) {
                return $perm['permission'] == 'allow';
            }
        }

        return false;
    }

    public function Permissions() {
        return $this->_permissions;
    }
 
}
