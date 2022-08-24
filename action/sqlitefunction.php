<?php

use dokuwiki\plugin\structpublish\meta\Assignments;

class action_plugin_structpublish_sqlitefunction extends DokuWiki_Action_Plugin
{

    /**
     * @inheritDoc
     */
    public function register(\Doku_Event_Handler $controller)
    {
        $controller->register_hook('STRUCT_PLUGIN_SQLITE_FUNCTION', 'BEFORE', $this, 'addFunctions');
    }

    public function addFunctions(Doku_Event $event)
    {
        $event->data[] = [
            'obj' => $this,
            'name' => 'IS_PUBLISHER'
        ];
    }

    public function IS_PUBLISHER()
    {
        global $USERINFO;
        global $INPUT;

        $args = func_get_args();
        $pid = $args[0];
        $userId = $args[1] ?? $INPUT->server->str('REMOTE_USER');
        $grps = $args[2] ?? ($USERINFO['grps'] ?? []);

        return $this->userHasRole(
            [\helper_plugin_structpublish_db::ACTION_PUBLISH, \helper_plugin_structpublish_db::ACTION_APPROVE],
            $userId,
            $grps,
            $pid
        );
    }

    protected function userHasRole($roles, $userId, $grps, $pid)
    {
        $assignments = Assignments::getInstance();
        $rules = $assignments->getPageAssignments($pid);

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
