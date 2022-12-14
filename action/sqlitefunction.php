<?php

use dokuwiki\plugin\structpublish\meta\Assignments;

class action_plugin_structpublish_sqlitefunction extends DokuWiki_Action_Plugin
{

    /** @inheritDoc */
    public function register(\Doku_Event_Handler $controller)
    {
        $controller->register_hook('STRUCT_PLUGIN_SQLITE_FUNCTION', 'BEFORE', $this, 'addFunctions');
    }

    /**
     * Register our own sqlite function
     *
     * @param Doku_Event $event
     * @return void
     */
    public function addFunctions(Doku_Event $event)
    {
        $event->data[] = [
            'obj' => $this,
            'name' => 'IS_PUBLISHER'
        ];
    }

    /**
     * Function registered in SQLite
     *
     * Params are read via function args
     *
     * @param string $pid The page id
     * @param string $userId The user name
     * @param string[] $groups A list of groups the user is member of
     * @return int Return an integer instead of boolean for better sqlite compatibility
     */
    public function IS_PUBLISHER()
    {
        /** @var helper_plugin_structpublish_db $helper */
        $helper = plugin_load('helper', 'structpublish_db');
        if (!$helper->isPublishable()) return 1;

        global $USERINFO;
        global $INPUT;

        $args = func_get_args();
        $pid = $args[0];
        $userId = $args[1] ?? $INPUT->server->str('REMOTE_USER');
        $grps = $args[2] ?? ($USERINFO['grps'] ?? []);

        return (int)$this->userHasRole(
            $pid,
            $userId,
            $grps
        );
    }

    /**
     * Check if a given user has role assignment for a given page
     *
     * @param string $pid Page to check
     * @param string $userId User login name, current user if empty
     * @param string[] $grps Groups the user has, current user's groups if empty user
     * @param string[] $roles Roles the user should have, empty for any role
     * @return bool
     */
    public static function userHasRole($pid, $userId = '', $grps = [], $roles = [])
    {
        global $INPUT;
        global $USERINFO;

        if (blank($userId)) {
            $userId = $INPUT->server->str('REMOTE_USER');
            $grps = $USERINFO['grps'] ?? [];
        }

        $assignments = Assignments::getInstance();
        $rules = $assignments->getPageAssignments($pid);

        // if no roles are given, any role is fine
        if (empty($roles)) {
            return auth_isMember(
                implode(',', array_merge(...array_values($rules))),
                $userId,
                $grps
            );
        }

        foreach ($roles as $role) {
            if (isset($rules[$role])) {
                $users = $rules[$role];
                if (auth_isMember(implode(',', $users), $userId, $grps)) {
                    return true;
                }
            }
        }

        return false;
    }
}
